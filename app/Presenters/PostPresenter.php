<?php

declare(strict_types=1);

namespace App\Presenters;

use Nette;
use App\Model\PostFacade;

final class PostPresenter extends Nette\Application\UI\Presenter
{
    private Nette\Database\Explorer $database;
    private PostFacade $facade;

    public function __construct(Nette\Database\Explorer $database, PostFacade $facade)
    {
        $this->database = $database;
        $this->facade = $facade;
    }

    public function renderShow(int $postId): void
    {
        $post = $this->database
            ->table('posts')
            ->get($postId);

        $postCount = $this->facade
            ->getPostCount();

        if (!$post) {
            if ($postId < 1){
                $postId = $postCount;
            }
            if ($postId > $postCount){
                $postId = 1;
            }
            $post = $this->database
                ->table('posts')
                ->get($postId);
            if (!$post){
                $this->error('Příspěvek nebyl nelezen');
            }
        }

        $this->template->post = $post;
    }
}