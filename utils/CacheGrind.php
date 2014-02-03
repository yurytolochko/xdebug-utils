<?php


class CacheGrind
{
	/**
	 * Functions calls profiles
	 */
	protected $functions = array();

	/**
	 * String name of main function
	 */
	const ENTRY_POINT = '{main}';


	/**
	 * Extract information from $inFile and store in preprocessed form in $outFile
	 *
	 * @param string $inFile Callgrind file to read
	 * @return void
	 **/
	public function parse($inFile)
	{
		$in = @fopen($inFile, 'rb');
		if (!$in)
			throw new Exception('Could not open ' . $inFile . ' for reading.');

		// Read information into memory
		while (($line = fgets($in))) {
			if (substr($line, 0, 3) === 'fl=') {
				// Found invocation of function. Read functionname
				list($function) = fscanf($in, "fn=%s");
				if (!isset($this->functions[$function])) {
					$this->functions[$function] = array(
						'filename' => substr(trim($line), 3),
						'invocationCount' => 0,
						'count' => 0,
						'summedSelfCost' => 0,
						'summedInclusiveCost' => 0
					);
				}
				$this->functions[$function]['invocationCount']++;
				// Special case for ENTRY_POINT - it contains summary header
				if (self::ENTRY_POINT == $function) {
					fgets($in);
					fgets($in);
					fgets($in);
				}
				// Cost line
				list($lnr, $cost) = fscanf($in, "%d %d");
				$this->functions[$function]['summedSelfCost'] += $cost;
				$this->functions[$function]['summedInclusiveCost'] += $cost;
			} else if (substr($line, 0, 4) === 'cfn=') {
				// Skip call line
				fgets($in);
				// Cost line
				list($lnr, $cost) = fscanf($in, "%d %d");
				$this->functions[$function]['summedInclusiveCost'] += $cost;

			}
		}
	}

	public function getFunctions()
	{
		return $this->functions;
	}

	public function summarize()
	{
		// order by function self cost
		uasort($this->functions, array($this, 'compareFunctions'));

		$totalTime = 0;
		foreach($this->functions as $statistic) {
			$totalTime+= $statistic['summedSelfCost'];
		}

		foreach($this->functions as $function => $statistic) {
			$this->functions[$function]['avgSelfCost'] = ceil($statistic['summedSelfCost'] / $statistic['invocationCount']);
			$this->functions[$function]['avgInclusiveCost'] = ceil($statistic['summedInclusiveCost'] / $statistic['invocationCount']);
			$this->functions[$function]['selfCostPercentage'] = round($statistic['summedSelfCost'] / $totalTime * 100, 2);
			$this->functions[$function]['summedInclusiveCostPercentage'] = round($statistic['summedInclusiveCost'] / $totalTime * 100, 2);
		}
	}

	protected function compareFunctions($a, $b)
	{
		if ($a['summedSelfCost'] == $b['summedSelfCost'])
			return 0;

		return ($a['summedSelfCost'] > $b['summedSelfCost']) ? -1 : 1;
	}

}