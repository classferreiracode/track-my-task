<?php

namespace App\Exceptions;

use DomainException;

class PlanLimitExceededException extends DomainException
{
    public function __construct(
        string $message,
        public string $limitKey,
        public ?int $limitValue,
        public ?int $currentValue,
        public ?string $upgradeUrl,
    ) {
        parent::__construct($message, 403);
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        return [
            'limit_key' => $this->limitKey,
            'limit_value' => $this->limitValue,
            'current_value' => $this->currentValue,
            'upgrade_url' => $this->upgradeUrl,
            'message' => $this->getMessage(),
        ];
    }
}
