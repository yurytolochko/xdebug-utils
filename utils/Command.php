<?php

abstract class Command
{
	/**
	 * @var Getopt
	 */
	protected $optionsContainer = null;

	abstract public function help();
	abstract public function declareOptions();

	public function setOptionsContainer(Getopt $container)
	{
		$this->optionsContainer = $container;
	}

	public function getOptionsContainer()
	{
		return $this->optionsContainer;
	}

	public function getOption($name, $default = null)
	{
		return $this->getOptionsContainer()->getOption($name) ?: $default;
	}

	public function getOperands()
	{
		return $this->getOptionsContainer()->getOperands();
	}
}
