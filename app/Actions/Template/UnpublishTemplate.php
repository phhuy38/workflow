<?php

namespace App\Actions\Template;

use App\Models\ProcessTemplate;

class UnpublishTemplate
{
    public function handle(ProcessTemplate $template): void
    {
        $template->update([
            'is_published' => false,
        ]);
    }
}
