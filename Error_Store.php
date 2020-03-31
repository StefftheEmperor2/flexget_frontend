<?php

/**
 * File for Class Error_Store
 *
 * @author     Stefan Bischoff
 * @version    $Id:$ 2020-03-31
 * @package
 * @subpackage
 *
 */

/**
 * Error_Store
 *
 * @author     Stefan Bischoff
 * @version    $Id:$ 2020-03-31
 * @package
 * @subpackage
 **/
class Error_Store
{
	protected $errors = [];

	public function add($error)
	{
		if ($error instanceof static)
		{
			foreach ($error->get_messages() as $message)
			{
				$this->add($message);
			}
		}
		elseif ($error instanceof Exception)
		{
			$prefix = '';
			$trace = $error->getTrace();
			if (count($trace))
			{
				$trace_item = reset($trace);
				if (isset($trace_item['class']) AND isset($trace_item['function']))
				{
					$prefix = '['.$trace_item['class'].'::'.$trace_item['function'].'] ';
				}
			}
			$this->add($prefix.$error->getMessage());
		}
		else
		{
			$this->errors[] = $error;
		}

		return $this;
	}

	public function get_messages()
	{
		return $this->errors;
	}

	public function is_empty()
	{
		return (count($this->get_messages()) === 0);
	}
}