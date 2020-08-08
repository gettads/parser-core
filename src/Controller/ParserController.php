<?php

namespace App\Controller;

use App\Entity\Parsing;
use App\Entity\Post;
use App\Exceptions\ParsingNotFoundException;
use App\Interfaces\BeautyPostsCropperInterface;
use App\Interfaces\ParserDispatcherInterface;
use App\Repository\ParsingRepository;
use App\Repository\PostRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Exceptions\PostNotFoundException;

/**
 * Class ParserController
 *
 * @package App\Controller
 */
class ParserController extends AbstractController
{
    /**
     * @var ParserDispatcherInterface
     */
    private $dispatcher;

    /**
     * @var ParsingRepository
     */
    private $parsingRepository;

    /**
     * @var PostRepository
     */
    private $postRepository;

    /**
     * @var BeautyPostsCropperInterface
     */
    private $cropper;

    /**
     * ParserController constructor.
     *
     * @param ParserDispatcherInterface $dispatcher
     * @param ParsingRepository $parsingRepository
     * @param PostRepository $postRepository
     * @param BeautyPostsCropperInterface $cropper
     */
    public function __construct(
        ParserDispatcherInterface $dispatcher,
        ParsingRepository $parsingRepository,
        PostRepository $postRepository,
        BeautyPostsCropperInterface $cropper
    ) {
        $this->dispatcher = $dispatcher;
        $this->parsingRepository = $parsingRepository;
        $this->postRepository = $postRepository;
        $this->cropper = $cropper;
    }

    /**
     * Routes in routes.yaml by https://symfony.com/doc/current/routing.html#creating-routes-as-annotations
     *
     * @return Response
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function index(): Response
    {
        $currentParsing = $this->parsingRepository->findLastOne();
        if ($currentParsing !== null) {
            $this->cropper->cropPostsForParsingEntity($currentParsing);
        }

        return $this->render('index/dashboard.html.twig', ['currentParsingState' => $currentParsing]);
    }

    /**
     * Routes in routes.yaml by https://symfony.com/doc/current/routing.html#creating-routes-as-annotations
     *
     * @return RedirectResponse
     */
    public function run(): RedirectResponse
    {
        $this->dispatcher->dispatch();
        return $this->redirectToRoute('parsing_current');
    }

    /**
     * Routes in routes.yaml by https://symfony.com/doc/current/routing.html#creating-routes-as-annotations
     *
     * @return Response
     */
    public function list(): Response
    {
        $historyParsings = $this->parsingRepository->findAllLimited();
        return $this->render('index/history.html.twig', [
            'historyParsings' => $historyParsings,
            'currentParsingState' => current($historyParsings),
        ]);
    }

    /**
     * Routes in routes.yaml by https://symfony.com/doc/current/routing.html#creating-routes-as-annotations
     *
     * @param Parsing $parsing
     *
     * @return Response
     *
     * @throws ParsingNotFoundException
     */
    public function view(Parsing $parsing): Response
    {
        if (null === $parsing) {
            throw new ParsingNotFoundException();
        }

        $this->cropper->cropPostsForParsingEntity($parsing);
        return $this->render('index/dashboard.html.twig', ['currentParsingState' => $parsing]);
    }

    /**
     * Routes in routes.yaml by https://symfony.com/doc/current/routing.html#creating-routes-as-annotations
     *
     * @param Parsing $parsing
     * @param Post $post
     *
     * @return Response
     *
     * @throws ParsingNotFoundException
     * @throws PostNotFoundException
     */
    public function postDetail(Parsing $parsing, Post $post): Response
    {
        if (null === $parsing) {
            throw new ParsingNotFoundException();
        } elseif ((int)$post->getParsing()->getId() !== $parsing->getId()) {
            throw new PostNotFoundException();
        }

        return $this->render('index/post.html.twig', ['post' => $post, 'currentParsingState' => $parsing]);
    }
}
