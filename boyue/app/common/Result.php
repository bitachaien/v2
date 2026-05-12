<?php

declare(strict_types=1);

namespace app\common;


class Result
{
    
    public const CODE_SUCCESS = 0;

    
    public const CODE_ERROR = 1;

    
    public const CODE_PARAM_ERROR = 400;

    
    public const CODE_UNAUTHORIZED = 401;

    
    public const CODE_FORBIDDEN = 403;

    
    public const CODE_NOT_FOUND = 404;

    
    public const CODE_SERVER_ERROR = 500;

    
    private int $code;

    
    private string $msg;

    
    private $data;

    
    private ?int $count;

    
    private function __construct(int $code, string $msg, $data = null, ?int $count = null)
    {
        $this->code  = $code;
        $this->msg   = $msg;
        $this->data  = $data;
        $this->count = $count;
    }

    
    public static function success($data = null, string $msg = '操作成功', ?int $count = null): self
    {
        return new self(self::CODE_SUCCESS, $msg, $data, $count);
    }

    
    public static function page(array $list, int $count, string $msg = '获取成功'): self
    {
        return new self(self::CODE_SUCCESS, $msg, $list, $count);
    }

    
    public static function fail(string $msg, int $code = self::CODE_ERROR, $data = null): self
    {
        return new self($code, $msg, $data);
    }

    
    public static function paramError(string $msg = '参数错误'): self
    {
        return new self(self::CODE_PARAM_ERROR, $msg);
    }

    
    public static function notFound(string $msg = '资源不存在'): self
    {
        return new self(self::CODE_NOT_FOUND, $msg);
    }

    
    public static function serverError(string $msg = '服务器错误'): self
    {
        return new self(self::CODE_SERVER_ERROR, $msg, []);
    }

    
    public function getCode(): int
    {
        return $this->code;
    }

    
    public function getMsg(): string
    {
        return $this->msg;
    }

    
    public function getData()
    {
        return $this->data;
    }

    
    public function isSuccess(): bool
    {
        return $this->code === self::CODE_SUCCESS;
    }

    
    public function toArray(): array
    {
        $result = [
            'code' => $this->code,
            'msg'  => $this->msg,
            'data' => $this->data,
        ];

        if ($this->count !== null) {
            $result['count'] = $this->count;
        }

        return $result;
    }

    
    public function toResponse(): \support\Response
    {
        return json($this->toArray());
    }

    
    public function __toString(): string
    {
        return json_encode($this->toArray(), JSON_UNESCAPED_UNICODE);
    }
}
