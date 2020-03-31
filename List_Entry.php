<?php

/**
 * File for Class List_Entry
 *
 * @author     Stefan Bischoff
 * @version    $Id:$ 2020-03-31
 * @package
 * @subpackage
 *
 */

/**
 * List_Entry
 *
 * @author     Stefan Bischoff
 * @version    $Id:$ 2020-03-31
 * @package
 * @subpackage
 **/
class List_Entry
{
	protected $title;
	protected $original_url;

	public static function factory($title, $original_url)
	{
		$instance = new static;
		$instance->set_title($title);
		$instance->set_original_url($original_url);

		return $instance;
	}

	/**
	 * @return mixed
	 */
	public function get_title()
	{
		return $this->title;
	}

	/**
	 * @param string $title
	 * @return List_Entry
	 */
	public function set_title($title)
	{
		$this->title = $title;
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function get_original_url()
	{
		return $this->original_url;
	}

	/**
	 * @param string $original_url
	 * @return List_Entry
	 */
	public function set_original_url($original_url)
	{
		$this->original_url = $original_url;
		return $this;
	}


}