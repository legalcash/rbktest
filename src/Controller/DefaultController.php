<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController as Controller;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;

use App\Entity\News;
use App\Repository\NewsRepository;

/**
 * DefaultController.
 */
class DefaultController extends Controller
{
    /**
     * Show News list
     * 
     * @Route("/", name="list")
     */
    public function list(
        NewsRepository $newsRepository
    )
    {
        
        $news = $newsRepository->findBy(
            [], 
            ['publishdate' => 'DESC'],
            15
        );
        $out = '';
        
        foreach($news as $newsItem)
        {
            $uri = $this->generateUrl('news', ['id' => $newsItem->getId()]);
            $title = $newsItem->getTitle();
            $out .= "<a href=\"{$uri}\">{$title}</a><br />";
        }
        return new Response($out);
    }

    /**
     * Show detailed news
     * 
     * @Route("/news/{id}", name="news")
     */
    public function news(News $news)
    {
        $out = "<div>
                    <h1>{$news->getTitle()}</h1><small>{$news->getPublishdate()->format('c')}</small><br />
                ";
        if($news->getImgPath()){
            $out .= "<img src=\"/upload/{$news->getImgPath()}\" /><br />";
        }
        $out .= "<p>{$news->getBody()}</p>";
        $out .= "<a href=\"/\">назад к листингу</a>";
        $out .= '</div>';
        return new Response($out);

    }
}