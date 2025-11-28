<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

trait HasDeleteConfirmation
{
    public bool $showDeleteModal = false;

    public ?int $recordToDelete = null;

    public string $recordNameToDelete = '';

    public function confirmDelete(int $id, string $name): void
    {
        $this->recordToDelete = $id;
        $this->recordNameToDelete = $name;
        $this->showDeleteModal = true;
    }

    public function cancelDelete(): void
    {
        $this->showDeleteModal = false;
        $this->recordToDelete = null;
        $this->recordNameToDelete = '';
    }

    public function executeDelete(): void
    {
        if ($this->recordToDelete) {
            $this->performDelete($this->recordToDelete);
        }

        $this->cancelDelete();
    }

    /**
     * Perform the actual deletion of the record.
     * Components using this trait must implement this method.
     */
    abstract protected function performDelete(int $id): void;
}
