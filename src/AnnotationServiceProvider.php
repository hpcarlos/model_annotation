<?php

namespace hpcarlos\model_annotation;

use Illuminate\Support\ServiceProvider;
use Log;

class AnnotationServiceProvider extends ServiceProvider {
    protected $commands = [
        'hpcarlos\model_annotation\AnnotateCommand'
    ];

    public function register() {
        $this->commands($this->commands);
    }
}
