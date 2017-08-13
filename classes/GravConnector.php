<?php
namespace Grav\Plugin\TNTSearch;

use Grav\Common\Grav;
use Grav\Common\Page\Page;
use Grav\Common\Twig\Twig;

class GravConnector extends \PDO
{
    public function __construct()
    {

    }


    public function query($query)
    {
        $counter = 0;
        $results = [];

        $config = Grav::instance()['config'];

        $filter = $config->get('plugins.tntsearch.filter');
        $default_process = $config->get('plugins.tntsearch.index_page_by_default');

        if ($filter && array_key_exists('items', $filter)) {
            $page = new Page;
            $collection = $page->collection($filter, false);
        } else {
            $collection = Grav::instance()['pages']->all();
            $collection->published()->routable();
        }

        foreach ($collection as $page) {
            $counter++;
            $process = $default_process;
            $header = $page->header();
            $route = $page->route();

            if (isset($header->tntsearch['process'])) {
                $process = $header->tntsearch['process'];
            }

            // Only process what's configured
            if (!$process) {
                echo("Skipped $counter $route\n");
                continue;
            }

            try {
                $mapping_fields = [
                    'id'      => $route,
                    'name'    => $page->title(),
                    'content' => GravTNTSearch::getCleanContent($page)
                ];
                $results[] = $mapping_fields;
                echo("Added $counter $route\n");
            } catch (\Exception $e) {
                echo("Skipped $counter $route\n");
                continue;
            }
        }

        return new GravResultObject($results);
    }

}

