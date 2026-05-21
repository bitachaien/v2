<?php

namespace plugin\admin\app\model;

/**
 * GSC+ Platform Model
 * 
 * @property integer $id Platform ID
 * @property integer $category_id Category ID
 * @property string $code Platform Code (e.g., PG, JILI)
 * @property string $name Platform Name
 * @property string $product_code GSC+ Product Code
 * @property float $pt_percentage PT Percentage
 * @property string $logo Platform Logo URL
 * @property integer $sort_order Display Order
 * @property integer $status Status: 1=Active, 0=Inactive
 * @property string $created_at Created Time
 * @property string $updated_at Updated Time
 */
class GscPlusPlatform extends Base
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'wa_gscplus_platforms';

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
        'category_id',
        'code',
        'name',
        'product_code',
        'pt_percentage',
        'logo',
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
        'category_id' => 'integer',
        'pt_percentage' => 'decimal:2',
        'sort_order' => 'integer',
        'status' => 'integer',
    ];

    /**
     * Get the category that owns the platform
     */
    public function category()
    {
        return $this->belongsTo(GscPlusGameCategory::class, 'category_id', 'id');
    }

    /**
     * Scope a query to only include active platforms.
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
     * Scope a query to filter by category
     */
    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    /**
     * Get platform by code and category
     */
    public static function findByCode(string $code, ?int $categoryId = null)
    {
        $query = static::where('code', $code);
        
        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }
        
        return $query->first();
    }

    /**
     * Get platforms by category code
     */
    public static function getByCategoryCode(string $categoryCode)
    {
        return static::whereHas('category', function ($query) use ($categoryCode) {
            $query->where('code', $categoryCode);
        })
        ->active()
        ->ordered()
        ->get();
    }

    /**
     * Get all platforms with category info
     */
    public static function getAllWithCategory()
    {
        return static::with('category')
            ->ordered()
            ->get();
    }

    /**
     * Get active platforms grouped by category
     */
    public static function getGroupedByCategory()
    {
        return static::active()
            ->with('category')
            ->ordered()
            ->get()
            ->groupBy('category_id');
    }
}