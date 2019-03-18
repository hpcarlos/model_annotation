<?php

namespace hpcarlos\model_annotation;
use AnnotatingClass;

use DB;
use Illuminate\Console\Command;


class AnnotateCommand extends Command {
    protected $name = 'annotate:models';

    protected $description = 'annotate models';

    public function __construct() {
        parent::__construct();
    }

    public function handle() {
      $result = DB::select("SELECT * FROM pg_catalog.pg_tables WHERE schemaname != 'pg_catalog' AND schemaname != 'information_schema' AND tablename not in ('migrations','oauth_auth_codes','oauth_access_tokens','oauth_refresh_tokens','oauth_clients','oauth_personal_access_clients')");
        foreach ($result as $key => $value) {
          Annotation::annotateTable(app(), $value->tablename);
        }
    }

    protected function getArguments() {
        return [];
    }

    protected function getOptions() {
        return [];
    }
}
