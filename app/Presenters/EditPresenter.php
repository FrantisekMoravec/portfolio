<?php

declare(strict_types=1);

namespace App\Presenters;

use Nette;
use Nette\Application\UI\Form;
use Nette\Utils\FileSystem;
use Nette\Http\FileUpload;

final class EditPresenter extends Nette\Application\UI\Presenter
{
    private Nette\Database\Explorer $database;

    public function __construct(Nette\Database\Explorer $database)
    {
        $this->database = $database;
    }

    public function startup(): void
    {
        parent::startup();

        if (!$this->getUser()->isLoggedIn()) {
            $this->redirect('Sign:in');
        }
    }

    public function renderEdit(int $postId): void
    {
        $post = $this->database
            ->table('posts')
            ->get($postId);

        if (!$post) {
            $this->error('Příspěvek nebyl nelezen');
        }

        $this->getComponent('postForm')
            ->setDefaults($post->toArray());

        $this->template->post = $post;
    }

    protected function createComponentPostForm(): Form
    {
        $form = new Form;
        $form->addText('title', 'Název díla:')
            ->setRequired("Bez jména nelze příspěvek uložit.");
        $form->addTextArea('description', 'Popis:');
        $form->addUpload('image', 'Nahrát výtvor:')
            ->setRequired("Bez obrázku nelze příspěvek uložit.");

        $form->addSubmit('send', 'Přidat příspěvek');
        $form->onSuccess[] = [$this, 'postFormSucceeded'];

        return $form;
    }

    public function postFormSucceeded(array $data): void
    {
        $postId = $this->getParameter('postId');

        if ($postId) {
            $post = $this->database
                ->table('posts')
                ->get($postId);

            $oldImage =  $post['image'];
            FileSystem::delete(__DIR__ . '/../../www/upload/' . $oldImage);

            $upload = $data['image'];
            $name = $upload->getSanitizedName();
            $nameOnDrive = time() . $name;
            @mkdir(__DIR__ . '/../../www/upload/');
            $upload->move(__DIR__ . '/../../www/upload/' . $nameOnDrive);

            $post->update([
                'title' => $data['title'],
                'description' => $data['description'],
                'image' => $nameOnDrive,
                'last_edited' => new \DateTime
            ]);
        } else {
            $upload = $data['image'];
            $name = $upload->getSanitizedName();
            $nameOnDrive = time() . $name;
            @mkdir(__DIR__ . '/../../www/upload/');
            $upload->move(__DIR__ . '/../../www/upload/' . $nameOnDrive);

            $post = $this->database
                ->table('posts')
                ->insert([
                    'title' => $data['title'],
                    'description' => $data['description'],
                    'image' => $nameOnDrive
                ]);
        }

        $this->flashMessage('Příspěvek byl úspěšně publikován', 'success');

        $this->redirect('Home:default');
    }
}