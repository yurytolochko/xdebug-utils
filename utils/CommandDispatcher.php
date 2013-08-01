<?php

class CommandDispatcher
{
	protected $arguments;
	protected $target;
	protected $options;
	protected $script;

	public function __construct($arguments, $defaultTarget)
	{
		$this->setArguments($arguments, $defaultTarget);
	}

	public function setArguments($arguments, $defaultTarget)
	{
		$this->arguments = $arguments;
		$this->script = array_shift($arguments);
		$this->target = isset($arguments[0]) && class_exists(ucfirst($arguments[0])) ? array_shift($arguments) : $defaultTarget;
		$this->options = $arguments;
	}

	public function dispatch()
	{
		$commandClass = ucfirst($this->target);
		if (!class_exists($commandClass))
			throw new Exception("can't find command " . $this->target);

		$command = new $commandClass();

		if (!empty($this->options) && $this->options[0] == "help") {
			$command->help();
		} else {

			$optionsContainer = new Getopt($command->declareOptions());
			$optionsContainer->parse($this->options);

			$command->setOptionsContainer($optionsContainer);
			$command->run();
		}
	}
}
