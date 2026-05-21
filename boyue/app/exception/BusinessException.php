<?php

declare(strict_types=1);

namespace app\exception;

use app\common\Result;


class BusinessException extends \RuntimeException
{
    
    protected int $errorCode;

    
    protected $data;

    
    public function __construct(
        string $message,
        int $errorCode = Result::CODE_ERROR,
        $data = null,
        ?\Throwable $previous = null
    ) {
        $this->errorCode = $errorCode;
        $this->data      = $data;

        parent::__construct($message, $errorCode, $previous);
    }

    
    public function getErrorCode(): int
    {
        return $this->errorCode;
    }

    
    public function getData()
    {
        return $this->data;
    }

    
    public function toResult(): Result
    {
        return Result::fail($this->getMessage(), $this->errorCode, $this->data);
    }

    
    public static function paramError(string $message = 'Tham số không hợp lệ'): self
    {
        return new static($message, Result::CODE_PARAM_ERROR);
    }

    
    public static function notFound(string $message = '资源không tồn tại'): self
    {
        return new static($message, Result::CODE_NOT_FOUND);
    }

    
    public static function businessError(string $message): self
    {
        return new static($message, Result::CODE_ERROR);
    }
}
