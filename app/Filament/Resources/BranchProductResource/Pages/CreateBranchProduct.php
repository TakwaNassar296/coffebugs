<?php

namespace App\Filament\Resources\BranchProductResource\Pages;

use App\Filament\Resources\BranchProductResource;
use App\Support\BranchProductAdmin;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateBranchProduct extends CreateRecord
{
    protected static string $resource = BranchProductResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return BranchProductAdmin::createOrMerge($data);
    }
}
