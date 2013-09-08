<?php

$autoloadFname = realpath(dirname(__FILE__) . '/../vendor') . '/autoload.php';
require_once($autoloadFname);

copy('fixtures.s3db', 'fixtures-run.s3db');
$StoreConfig = \StoreSqlSqlite3Config::serve()
	->setPath(realpath(dirname(__FILE__)))
	->setDatabase('fixtures-run.s3db');
\Camarera::registerStore($StoreConfig);
