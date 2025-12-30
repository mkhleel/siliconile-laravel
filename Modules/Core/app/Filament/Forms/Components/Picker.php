<?php

namespace Modules\Core\Filament\Forms\Components;

use Closure;
use Filament\Forms\Components\Concerns\HasOptions;
use Filament\Forms\Components\Field;
use Filament\Support\Concerns\HasExtraAlpineAttributes;

class Picker extends Field
{
    protected string $view = 'core::forms.components.picker';

    use HasExtraAlpineAttributes;
    use HasOptions;

    protected array|Closure $icons = [];

    protected array|Closure $images = [];

    protected int|Closure|null $imageSize = null;

    protected bool|Closure $imageOnly = false;

    protected bool|Closure $multiple = false;

    protected function setUp(): void
    {
        parent::setUp();

    }

    public function icons(array|Closure $icons): static
    {
        $this->icons = $icons;

        return $this;
    }

    public function getIcons(): array
    {
        return (array) $this->evaluate($this->icons);
    }

    public function images(array|Closure $images)
    {
        $this->images = $images;

        return $this;
    }

    public function getImages(): array
    {
        return (array) $this->evaluate($this->images);
    }

    public function imageSize(int|Closure $size): static
    {
        $this->imageSize = $size;

        return $this;
    }

    public function getImageSize(): int
    {
        return (int) $this->evaluate($this->imageSize);
    }

    public function imageOnly(bool|Closure $condition = true): static
    {
        $this->imageOnly = $condition;

        return $this;
    }

    public function getImageOnly()
    {
        return $this->evaluate($this->imageOnly);
    }

    public function multiple(bool|Closure $multiple = true): static
    {
        $this->multiple = $multiple;

        return $this;
    }

    public function getMultiple()
    {
        return $this->evaluate($this->multiple);
    }
}
