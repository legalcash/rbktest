<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\AbstractContract;
use App\Entity\News;
use App\Repository\UserRepository;
use Assert\Assertion;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpClient\Response\CurlResponse;
use Symfony\Component\DomCrawler\Crawler;
use App\Repository\NewsRepository;

class RemoteImageService
{

    /**
     * @var HttpClientInterface
     */
    private $_httpClient;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var NewsRepository
     */
    private $newsRepository;

    private $kernel_project_dir = null;



    public function __construct(
        HttpClientInterface $httpClient,
        EntityManagerInterface $entityManager,
        NewsRepository $newsRepository,
        $kernel_project_dir
    )
    {
        $this->_httpClient          = $httpClient;
        $this->entityManager        = $entityManager;
        $this->newsRepository       = $newsRepository;
        $this->kernel_project_dir   = $kernel_project_dir;
    }
    
    public function getImageByUrl(string $url, string $name = 'default.jpg'): UploadedFile
    {
        $path = $this->kernel_project_dir . '/var/upload/'.$name; // это в настройки надо бы скинуть

        $data = \file_get_contents($url); // тут надо бы обработать исключения
        \file_put_contents($path, $data);
        
        return new UploadedFile($path, $name, null, null, true);
    }

    /**
     * @return array структурироавнных данных (можно было бы описать структуру но в целом нам важен только url пока что)
     */
    public function getRemoteNewsList(int $limit = 15): array
    {
        $jsonListUrl = 'https://www.rbc.ru/v10/ajax/get-news-feed/project/rbcnews/lastDate/3601292400/limit/30';

        // использую $this->_httpClient так как там сразу дефолтные настройки клиента - не надо донастраивать как file_get_contents (там 404 ответ)
        $curlResponse = $this->_httpClient->request(
            'GET',
            $jsonListUrl
        );

        if (!\in_array($curlResponse->getStatusCode(), [200, 201, 202, 203, 204], true)) {
            throw new \Exception('Connect error');
        }

        $response = $this->_getCurlResult($curlResponse);
        
        $links = [];
        foreach($response['items'] as $item)
        {   
            $matches = $textMatches = null;
            if( \preg_match('/href="(https:\/\/www.rbc.ru\/[^"]+)"/', $item['html'], $matches) )
            {
                \preg_match('/<span class="news-feed__item__title[^"]*">([^<]+)<\/span>/', $item['html'], $textMatches);
                $links[] = ['publishdate' => \DateTime::createFromFormat('U', (string)$item['publish_date_t']), 'url' => $matches[1], 'title' => \trim($textMatches[1]??''), ];//, 'meta' => $item['html']];
            }
            if(15 === count($links)) break; //среди ссылок попадаются рекламные поэтому по грубому взял 30 и отфильтровал и ограничил до 15
        }

        return $links;
    }

    /**
     * Получаем статью и сразу парсим ее на части возвращая структурированный массив данных по ней
     */
    public function getRemoteNews(string $url): array
    {
        $curlResponse = $this->_httpClient->request(
            'GET',
            $url
        );

        if (!\in_array($curlResponse->getStatusCode(), [200, 201, 202, 203, 204], true)) {
            throw new \Exception('Connect error');
        }

        $response = $curlResponse->getContent(); // тут у нас не json а чистый html

        $crawler = new Crawler($response);
        $imgCrawler = $crawler->filter('img.article__main-image__image');
        $img = null;
            try{
            $img = $imgCrawler->attr('src');
            }catch(\Exception $e){
                // dd($imgCrawler->count(), $e);
                $img = null;
            }

        $textCrawler = $crawler->filter('div.article__text p')->each(function (Crawler $node, $i) {
            return $node->text();
        });

        
        return [
            // 'publishdate' => 1603286574,
            'title' => 'none',
            'body' => join('<br />', $textCrawler),
            'img' => $img //'https://s0.rbk.ru/v6_top_pics/resized/1180xH/media/img/8/74/756032814147748.jpg'
        ];
    }




    // надо бы в отдельный сервис уровня domain (DDD)
    public function getAll(): array
    {
        $e = [];
        // 1. Получить список новостей
        $newsList = $this->getRemoteNewsList();
        
        $this->newsRepository->deleteAll();
        // 2. для каждой новости получить ее контент
        foreach($newsList as $key=>$newsListItem){
            $news = $this->getRemoteNews($newsListItem['url']);
            // создаем сущность новости: 
            $newsEntity = new News();
            
            $newsEntity->setTitle($newsListItem['title']);
            $newsEntity->setBody($news['body']);
            $dt = $newsListItem['publishdate']; // у нас данные в timestamp -> datetime
            $newsEntity->setPublishdate($dt);

            if(isset($news['img'])) { // если есть картинка загружаем ее
                $uploadedImage = $this->getImageByUrl($news['img'], 'tmp_'.$key.'.jpg'); // грузим картинку во временный файл (эмуляция tmp для upload file)
                $newsEntity->setImgFile($uploadedImage); // на хуке доктрины произойет генерация случайного имени и фактический перенос файла в публичную часть
            }

            
            $this->entityManager->persist($newsEntity);
            $this->entityManager->flush();
            $e[] = $newsEntity;
        }

        return $e;
    }




    protected function _getCurlResult(CurlResponse $response)
    {
        $content         = $response->getContent();
        $decoded_content = \json_decode($content, true);

        return $decoded_content;
    }
}