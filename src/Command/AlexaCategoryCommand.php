<?php

namespace App\Command;

use Goutte\Client;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Link;

class AlexaCategoryCommand extends Command
{
    protected static $defaultName = 'alexa:category';

    private $categories = ['https://www.alexa.com/topsites/category/World/Persian'];

    protected function configure()
    {
        $this
            ->setDescription('Add a short description for your command')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $client = new Client();
        while (!empty($this->categories)) {
            $current = array_pop($this->categories);
            $io->title($current);
            $crawler = $client->request('get', $current);
            $this->addCategories($crawler);
            $links = $this->getLinks($crawler);
            if (!empty($links)) {
                $links = array_map(function (Link $link) {
                    return $link->getUri();
                }, $links);
                $io->listing($links);
                file_put_contents(__DIR__.'/../../var/log/alexa.txt', implode("\n", $links)."\n", FILE_APPEND);
            }
        }
    }

    public function addCategories($crawler)
    {
        $categories = $crawler->filter('#subcat_div > div > ul > li > a')->links();
        if (!empty($categories)) {
            $categories = array_map(function (Link $link) {
                return $link->getUri();
            }, $categories);
            $this->categories = array_merge($this->categories, $categories);
        }
    }

    /**
     * @param $crawler
     *
     * @return Crawler
     */
    public function getLinks($crawler)
    {
        return $crawler->filter('#alx-content div.td.DescriptionCell > p > a')->links();
    }
}
