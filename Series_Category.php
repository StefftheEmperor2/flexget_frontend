<?php
/**
 * Created by PhpStorm.
 * User: stefan
 * Date: 20.06.15
 * Time: 16:50
 */

class Series_Category
{
	protected $file;
	protected $name;
	protected $title_unique;

	protected $track_changes_to_csv = FALSE;
	protected $track_changes_to_lists = FALSE;
	protected $store;
	protected $series_store;

	/**
	 * @return mixed
	 */
	public function get_store()
	{
		return $this->store;
	}

	/**
	 * @param mixed $store
	 */
	public function set_store($store)
	{
		$this->store = $store;
	}

	/**
	 * @return mixed
	 */
	public function get_file()
	{
		return $this->file;
	}

	protected function check_dir($dir)
	{
		$segments = explode(DIRECTORY_SEPARATOR, $dir);
		$current_dir_segments = [];
		foreach ($segments as $segment) {
			$current_dir_segments[] = $segment;
			$current_dir = implode(DIRECTORY_SEPARATOR, $current_dir_segments);
			if (count($current_dir_segments) === 1 AND DIRECTORY_SEPARATOR === '/') {
				$current_dir = '/';
			}
			if ( ! is_dir($current_dir))
			{
				if ( ! mkdir($current_dir) && !is_dir($current_dir))
				{
					throw new \RuntimeException(sprintf('Directory "%s" was not created', $current_dir));
				}
			}
		}
	}

	protected function touch($filename)
	{
		$segments = explode(DIRECTORY_SEPARATOR, $filename);
		$dirname = implode(DIRECTORY_SEPARATOR, array_slice($segments, 0, -1));
		$this->check_dir($dirname);
		touch($filename);
	}

	protected function get_from_csv($file)
	{
		if (!file_exists($file))
		{
			$this->touch($file);
		}

		$fh = fopen($file, 'r');
		$added_array = array();
		if ($fh !== FALSE) {
			while (($added_line = fgetcsv($fh)) !== FALSE) {
				$added_array[] = $added_line;
			}

			fclose($fh);
		}

		if ($added_array === FALSE) {
			$added_array = array();
		}
		$added_items = array();

		foreach ($added_array as $added_item) {
			if (is_array($added_item)) {
				$added_item = reset($added_item);
			}
			if (empty($added_item)) {
				continue;
			}
			$added_object = new Series();
			$added_object->set_name($added_item);
			$added_object->set_category($this);
			$added_items[] = $added_object;
		}

		return $added_items;
	}

	/**
	 * @param mixed $file
	 */
	public function set_file($file)
	{
		if (!file_exists($file)) {
			$this->touch($file);
		}

		if (!function_exists('yaml_parse_file')) {
			die('pecl extension yaml missing');
		}
		$series = @yaml_parse_file($file);

		if ($series === FALSE OR !isset($series['series']['default'])) {
			$series = array('series' => array('default' => array()));
		}

		$series = $series['series']['default'];
		$this->series_store = new Series_Store();
		foreach ($series as $serie) {
			$series_object = new Series();
			$series_object->set_name($serie);
			$series_object->set_category($this);

			$this->series_store->add($series_object, FALSE);
		}

		if ($this->track_changes_to_csv)
		{
			$added_file = substr($file, 0, strlen($file) - 4) . '_added.csv';
			$removed_file = substr($file, 0, strlen($file) - 4) . '_removed.csv';

			$added_items = $this->get_from_csv($added_file);
			$this->get_series_store()->set_added($added_items);

			$removed_items = $this->get_from_csv($removed_file);
			$this->get_series_store()->set_removed($removed_items);
		}

		$this->file = $file;
	}

	/**
	 * @return bool
	 */
	public function get_track_changes_to_csv()
	{
		return $this->track_changes_to_csv;
	}

	/**
	 * @param bool $track_changes_to_csv
	 */
	public function set_track_changes_to_csv($track_changes_to_csv)
	{
		$this->track_changes_to_csv = $track_changes_to_csv;
	}

	/**
	 * @return bool
	 */
	public function get_track_changes_to_lists()
	{
		return $this->track_changes_to_lists;
	}

	/**
	 * @param bool $track_changes_to_lists
	 */
	public function set_track_changes_to_lists($track_changes_to_lists)
	{
		$this->track_changes_to_lists = $track_changes_to_lists;
	}


	public function save()
	{
		$series_store = $this->get_series_store();
		$series = $series_store->get_series();
		$series_array = array();

		foreach ($series as $serie) {
			$series_array[] = $serie->get_name();
		}

		$added = $removed = array();

		foreach ($series_store->get_added() as $added_item) {
			$added[] = List_Entry::factory($added_item->get_name(), 'http://flexget.com');
		}

		foreach ($series_store->get_removed() as $removed_item) {
			$removed[] = List_Entry::factory($removed_item->get_name(), 'http://flexget.com');
		}

		if ($this->get_track_changes_to_csv())
		{
			$fh = fopen(substr($this->get_file(), 0, -4) . '_added.csv', 'w');
			foreach ($added as $added_line) {
				fputcsv($fh, [$added_line->get_name(), $added_line->get_url()]);
			}
			fclose($fh);

			$fh = fopen(substr($this->get_file(), 0, -4) . '_removed.csv', 'w');
			foreach ($removed as $removed_line) {
				fputcsv($fh, [$removed_line->get_name(), $removed_line->get_url()]);
			}
			fclose($fh);
		}

		if ($this->get_track_changes_to_lists())
		{
			foreach ($added as $added_item)
			{
				$this->get_store()->add_to_list($this->get_name().'_added_items', $added_item);
			}

			foreach ($removed as $removed_item)
			{
				$this->get_store()->add_to_list($this->get_name().'_removed_items', $removed_item);
			}
		}
		yaml_emit_file($this->get_file(), array('series' => array('default' => $series_array)));
	}

	public function get_series_store()
	{
		if ( ! isset($this->series_store))
		{
			$this->series_store = new Series_Store;
		}

		return $this->series_store;
	}

	/**
	 * @return mixed
	 */
	public function get_name()
	{
		return $this->name;
	}

	/**
	 * @param mixed $name
	 */
	public function set_name($name)
	{
		$this->name = $name;
	}

	/**
	 * @return mixed
	 */
	public function get_title_unique()
	{
		return $this->title_unique;
	}

	/**
	 * @param mixed $title_unique
	 */
	public function set_title_unique($title_unique)
	{
		$this->title_unique = $title_unique;
	}

	public function get_is_changed()
	{
		return $this->get_series_store()->get_is_changed();
	}
}