<?php

namespace App\Observers;

use App\Models\Form;
use App\Services\PublishedFormResolver;

class FormObserver
{
    public function __construct(private readonly PublishedFormResolver $publishedForms) {}

    public function saved(Form $form): void
    {
        $this->publishedForms->forget($form->slug);
        $originalSlug = (string) $form->getOriginal('slug');
        if ($originalSlug !== '' && $originalSlug !== $form->slug) {
            $this->publishedForms->forget($originalSlug);
        }
    }

    public function deleted(Form $form): void
    {
        $this->publishedForms->forget($form->slug);
    }
}
