<?php
/**
 * xdebug.profiler output analyzer
 */
class Profiler extends Command
{
	const FORMAT_CSV = 'csv';
	const FORMAT_TABLE = 'table';

	protected $header = array(
		'Method',
		'Invocations',
		'Self Cost (%)',
		'Self Cost (ms)',
		'Avg Self Cost (ms)',
		'Summed Incl. Cost (ms)',
		'Avg Incl. Cost (ms)',
	);

	public function declareOptions()
	{
		return array(
			array('r', 'report', Getopt::REQUIRED_ARGUMENT),
			array('t', 'top', Getopt::REQUIRED_ARGUMENT),
			array('f', 'format', Getopt::REQUIRED_ARGUMENT),
		);
	}

	public function help()
	{
		echo "xdebug.profiler output analyzer" . PHP_EOL . PHP_EOL;
		echo "Options:" . PHP_EOL;
		echo " -r, --report    Output file" . PHP_EOL;
		echo " -f, --format    Output format: table or csv" . PHP_EOL;
		echo " -t, --top       Show only first number of result line. Could be number or percentage: 10 or 20%" . PHP_EOL . PHP_EOL;
		echo "Usage examples:" . PHP_EOL;
		echo " Get full report to file" . PHP_EOL;
		echo " > ./xdebug profiler -r report.csv cachegrind.out.1340990144_014725" . PHP_EOL;
		echo " Get top 10 slow methods" . PHP_EOL;
		echo " > ./xdebug profiler -t 10 cachegrind.out.1340990144_014725" . PHP_EOL;
		echo " Get first 20% of slow methods for all files in folder" . PHP_EOL;
		echo " > ./xdebug profiler -t 20% /var/data/path-to-folder-with-profiles/" . PHP_EOL;

	}

	public function run()
	{
		$files = $this->getFiles();
		$report = $this->getOption('report', 'php://stdout');
		$format = $this->getOption('format', $report == 'php://stdout' ? self::FORMAT_TABLE : self::FORMAT_CSV);
		$top = $this->getOption('top');

		if (empty($files))
			throw new Exception('Please, specify cachegrind files');

		if (!in_array($format, array(self::FORMAT_CSV, self::FORMAT_TABLE)))
			throw new Exception('Unknown format: ' . $format);

		$functions = $this->getFunctions($files);
        $totals = $this->getTotals($functions);
		if (!empty($top)) {
			if (rtrim($top, '%') != $top) {
				$top = rtrim($top, '%');
				$functions = $this->topPercentage($functions, intval($top));
			} else {
				$functions = $this->top($functions, intval($top));
			}
		}

		if ($format == self::FORMAT_CSV) {
			$this->formatCsv($report, $functions);
		} elseif ($format == self::FORMAT_TABLE) {
			$this->formatTable($report, $functions, $totals);
		}
	}

	protected function getFiles()
	{
		$files = array();
		$profilerDefaultDir = ini_get('xdebug.profiler_output_dir');
		foreach($this->getOperands() as $target) {
			if (is_dir($target)) {
				foreach(scandir($target) as $key => $file) {
					if (is_file($target . $file)) {
						$files[] = $target . $file;
					}
				}
			} elseif (is_file($target)) {
				$files[] = $target;
			} elseif (is_file($profilerDefaultDir . $target)) {
				$files[] = $profilerDefaultDir. $target;
			}
		}
		return $files;
	}

	protected function getFunctions($files)
	{
		$cacheGrind = new CacheGrind();
		foreach($files as $file) {
			$cacheGrind->parse($file);
		}
		$cacheGrind->summarize();

		return $cacheGrind->getFunctions();
	}

	protected function formatCsv($report, $functions)
	{
		$out = fopen($report, 'w');

		if (!$out)
			throw new Exception("Coundn't write to " . $report);

		fputcsv($out, $this->header, ";");
		foreach ($functions as $function => $summary) {
			fputcsv($out, array(
				$function,
				$summary['invocationCount'],
				$summary['selfCostPercentage'],
				round($summary['summedSelfCost'] / 1000, 2),
				round($summary['avgSelfCost'] / 1000, 2),
				round($summary['summedInclusiveCost'] / 1000, 2),
				round($summary['avgInclusiveCost'] / 1000, 2),
			), ";");
		}
		fclose($out);
	}

	protected function formatTable($report, $functions, $totals)
	{
		$maxFunctionName = 0;
		foreach(array_keys($functions) as $function) {
			if (strlen($function) > $maxFunctionName)
				$maxFunctionName = strlen($function);
		}
		if ($maxFunctionName > 60)
			$maxFunctionName = 60;

		$format = "| %-{$maxFunctionName}s | %11s | %9s | %9s | %13s | %10s | %14s |" . PHP_EOL;

		$out = fopen($report, 'w');

		fwrite($out, sprintf($format, str_repeat('-', $maxFunctionName), str_repeat('-', 11), str_repeat('-', 9), str_repeat('-', 9), str_repeat('-', 13), str_repeat('-', 10), str_repeat('-', 14)));
		fwrite($out, sprintf($format, 'Method', 'Invocations', 'Self Cost', 'Self Cost', 'Avg Self Cost', 'Incl. Cost', 'Avg Incl. Cost'));
		fwrite($out, sprintf($format, str_repeat('-', $maxFunctionName), str_repeat('-', 11), str_repeat('-', 9), str_repeat('-', 9), str_repeat('-', 13), str_repeat('-', 10), str_repeat('-', 14)));

		foreach($functions as $function => $summary) {
			fwrite($out, sprintf($format,
				substr($function, 0, $maxFunctionName),
				$summary['invocationCount'],
				$summary['selfCostPercentage'] . '%',
				round($summary['summedSelfCost'] / 1000, 2) . 'ms',
				round($summary['avgSelfCost'] / 1000, 2) . 'ms',
				round($summary['summedInclusiveCost'] / 1000, 2) . 'ms',
				round($summary['avgInclusiveCost'] / 1000, 2) . 'ms'
			));
		}

		fwrite($out, sprintf($format, str_repeat('-', $maxFunctionName), str_repeat('-', 11), str_repeat('-', 9), str_repeat('-', 9), str_repeat('-', 13), str_repeat('-', 10), str_repeat('-', 14)));

        fwrite($out, sprintf($format,
			'Total',
			$totals['invocationCount'],
			'100%',
			round($totals['cost'] / 1000, 2) . 'ms',
			'',
			'',
			''
		));

        fwrite($out, sprintf($format, str_repeat('-', $maxFunctionName), str_repeat('-', 11), str_repeat('-', 9), str_repeat('-', 9), str_repeat('-', 13), str_repeat('-', 10), str_repeat('-', 14)));

		fclose($out);
 	}

	protected function top($functions, $limit)
	{
		return array_slice($functions, 0, $limit);
	}

	protected function topPercentage($functions, $limit)
	{
		$lastIndex = 0;
		$percentage = 0;
		foreach($functions as $statistic) {
			$lastIndex++;
			$percentage += $statistic['selfCostPercentage'];
			if ($percentage >= $limit)
				return array_slice($functions, 0, $lastIndex);
		}
		return $functions;
	}

    protected function getTotals($functions)
    {
        $total = array('cost' => 0, 'invocationCount' => 0);
        $totalInvocationCount = 0;
    	foreach($functions as $function => $summary) {
            $total['cost'] += $summary['summedSelfCost'];
            $total['invocationCount'] += $summary['invocationCount'];
    	}
        return $total;
    }
}