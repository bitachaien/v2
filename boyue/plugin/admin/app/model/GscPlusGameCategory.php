<?php

namespace plugin\admin\app\model;

/**
 * GSC+ Game Category Model
 * 
 * @property integer $id Category ID
 * @property string $code Category Code (e.g., NO_HU, CASINO)
 * @property string $name Category Name
 * @property string $gsc_type GSC+ Game Type (e.g., SLOT, LIVE_CASINO)
 * @property string $description Category Description
 * @property string $icon Category Icon
 * @property integer $sort_order Display Order
 * @property integer $status Status: 1=Active, 0=Inactive
 * @property string $created_at Created Time
 * @property string $updated_at Updated Time
 */
class GscPlusGameCategory extends Base
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'wa_gscplus_game_categories';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'code',
        'name',
        'gsc_type',
        'description',
        'icon',
        'sort_order',
        'status'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'sort_order' => 'integer',
        'status' => 'integer',
    ];

    /**
     * Get platforms for this category
     */
    public function platforms()
    {
        return $this->hasMany(GscPlusPlatform::class, 'category_id', 'id');
    }

    /**
     * Get active platforms for this category
     */
    public function activePlatforms()
    {
        return $this->hasMany(GscPlusPlatform::class, 'category_id', 'id')
            ->where('status', 1)
            ->orderBy('sort_order', 'asc');
    }

    /**
     * Scope a query to only include active categories.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    /**
     * Scope a query to order by sort order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order', 'asc');
    }

    /**
     * Get category by code
     */
    public static function findByCode(string $code)
    {
        return static::where('code', $code)->first();
    }

    /**
     * Get all active categories with platforms
     */
    public static function getAllWithPlatforms()
    {
        return static::active()
            ->ordered()
            ->with('activePlatforms')
            ->get();
    }
}