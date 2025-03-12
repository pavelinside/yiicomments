<?php
include_once 'xhprof_lib/utils/xhprof_lib.php';
include_once 'xhprof_lib/utils/xhprof_runs.php';

\xhprof_enable(\XHPROF_FLAGS_CPU + \XHPROF_FLAGS_MEMORY);

sleep(5);

// Выключаем профайлинг и сохраняем данные
$xhprof_data = \xhprof_disable();
$xhprof_runs = new \XHProfRuns_Default();
$run_id = $xhprof_runs->save_run($xhprof_data, "xhprof_testing");
// Формируем ссылку на данные профайлинга и записываем ее в консоль
$link = "http://" . $_SERVER['HTTP_HOST'] . "/xhprof_html/index.php?run={$run_id}&source=xhprof_testing\n";
echo $link;

die;
