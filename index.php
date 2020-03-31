<?php
/**
 * Created by PhpStorm.
 * User: stefan
 * Date: 20.06.15
 * Time: 16:31
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

//define('BASE_DIR','/media/raspi/flexget');
if (getenv('BASE_DIR')) {
	define('BASE_DIR', getenv('BASE_DIR'));
}
else {
	define('BASE_DIR', '/opt/etc/flexget');
}
define('SERIES_GERMAN', BASE_DIR . '/series_german.yml');
define('SERIES_GERMAN_SUBBED', BASE_DIR . '/series_german_subbed.yml');
define('SERIES_ENGLISH', BASE_DIR . '/series_english.yml');

define('MOVIE_QUEUE', BASE_DIR . '/movie_queue.yml');

define('MOVIES_GERMAN', BASE_DIR . '/movies_german.yml');
define('MOVIES_GERMAN_SUBBED', BASE_DIR . '/movies_german_subbed.yml');
define('MOVIES_ENGLISH', BASE_DIR . '/movies_english.yml');

require_once 'Category_Store.php';
require_once 'Category_Series.php';
require_once 'Series_Category_Store.php';
require_once 'Series.php';
require_once 'Series_Store.php';
require_once 'Series_Category.php';
require_once 'List_Entry.php';
require_once 'Flexget_Api.php';
require_once 'Error_Store.php';
require_once 'Api_Exception.php';
require_once 'Api_Curl_Exception.php';
require_once 'Api_Http_Exception.php';

$api_url = NULL;
$api_password = NULL;
$flexget_api_password_file = '/dev/secrets/flexget_api';

if (getenv('API_URL')) {
	$api_url = getenv('API_URL');
}
if (file_exists($flexget_api_password_file) AND is_readable($flexget_api_password_file))
{
	$api_password = file_get_contents($flexget_api_password_file);
}
elseif (getenv('API_PASSWORD'))
{
	$api_password = getenv('API_PASSWORD');
}

/**
 * @var Series_Category[] $categories
 */
$categories = array();

$series_store = new Series_Category_Store;
$series_store->set_name('series');
$series_store->set_base_dir(BASE_DIR);
$series_store->set_api_username('flexget');
if (isset($api_url))
{
	$series_store->set_api_url($api_url);
}
if (isset($api_password))
{
	$series_store->set_api_password($api_password);
}
$movie_store = new Series_Category_Store;
$movie_store->set_name('movies');
$movie_store->set_base_dir(BASE_DIR);
$movie_store->set_api_username('flexget');
if (isset($api_url))
{
	$movie_store->set_api_url($api_url);
}
if (isset($api_password))
{
	$movie_store->set_api_password($api_password);
}
$series_german = new Series_Category;
$series_german->set_file(SERIES_GERMAN);
$series_german->set_name('Deutsch');
$series_german->set_title_unique('series_german');
$series_german->set_track_changes_to_csv(FALSE);
$series_store->add($series_german);
$categories[] = $series_german;

$series_german_subbed = new Series_Category();
$series_german_subbed->set_file(SERIES_GERMAN_SUBBED);
$series_german_subbed->set_name('Deutsche Untertitel');
$series_german_subbed->set_title_unique('series_german_subbed');
$series_german_subbed->set_track_changes_to_csv(FALSE);
$series_store->add($series_german_subbed);
$categories[] = $series_german_subbed;

$series_english = new Series_Category();
$series_english->set_file(SERIES_ENGLISH);
$series_english->set_name('Englisch');
$series_english->set_title_unique('series_english');
$series_english->set_track_changes_to_csv(FALSE);
$series_store->add($series_english);
$categories[] = $series_english;

$movie_queue = new Series_Category();
$movie_queue->set_file(MOVIE_QUEUE);
$movie_queue->set_name('Warteschlange');
$movie_queue->set_title_unique('movie_queue');
$movie_queue->set_track_changes_to_lists(TRUE);
$movie_queue->set_track_changes_to_csv(FALSE);
$movie_store->add($movie_queue);
$categories[] = $movie_queue;

$error_store = new Error_Store;
$movie_store->process_post();
$series_store->process_post();
$error_store->add($movie_store->get_error_store());
$error_store->add($series_store->get_error_store());
?>
<!DOCTYPE html>
<html>
<head>
	<title>Flexget Config</title>
	<link rel="stylesheet" type="text/css" href="/css/style.css">
	<script type="text/javascript" language="JavaScript" src="/js/jquery-2.1.4.min.js"></script>
	<script type="text/javascript" language="JavaScript" src="/js/html.js"></script>
	<meta charset="utf-8">
</head>
<body>
<div class="tabbed">

	<div class="headlines">
		<img class="header_image" src="/img/flexget.png">
		<div class="tabbed_bar">
			<div class="headline" data-category="movies">Filme</div>
			<div class="headline" data-category="series">Serien</div>
		</div>
	</div>
	<div class="content">
<?php
		if ( ! $error_store->is_empty())
		{
?>
			<div class="errors">
				<?php
				foreach ($error_store->get_messages() as $message)
				{
?>
					<div class="error"><?php echo $message; ?></div>
<?php
				}
?>
			</div>
<?php
		}
?>
			<div>
		<div class="category" data-category="movies"><?php echo $movie_store->get_html(); ?></div>
		<div class="category" data-category="series"><?php echo $series_store->get_html(); ?></div>
	</div>
</div>
</body>
</html>
