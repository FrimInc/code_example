<?php

namespace App\Controller;

use App\Controller\Traits\AccessControllerTrait;
use App\Exceptions\FieldValidateException;
use App\Repository\RecipeRepository;
use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class Recipe extends PageController
{
    use AccessControllerTrait;

    protected RecipeRepository $recipeRepository;

    private Request $obRequest;

    /**
     * Recipes constructor.
     *
     * @param RecipeRepository $recipeRepository
     */
    public function __construct(RecipeRepository $recipeRepository)
    {
        $this->recipeRepository = $recipeRepository;
        $this->obRequest        = Request::createFromGlobals();
        $this->obRepository     = $this->recipeRepository;
    }

    /**
     * @param Request $obRequest
     *
     * @Route("/app/recipes")
     *
     * @return Response
     * @throws Exception
     */
    public function index(Request $obRequest): Response
    {
        $arRecipes = $this->recipeRepository->getVisibleForUser(
            $this->getTUser(),
            $obRequest->getContent() ? $obRequest->toArray() : $obRequest->request->all()
        );
        foreach ($arRecipes as $obRecipe) {
            $obRecipe->makeRestrict($this->getTUser());
        }
        return $this->json($arRecipes);
    }

    /**
     * @param int $id
     *
     * @Route("/app/recipe/{id<\d+>}")
     *
     * @return Response
     */
    public function view(int $id): Response
    {
        if ($id) {
            $obRecipe = $this->recipeRepository->getVisibleByID($id, $this->getTUser());
            if (!$obRecipe) {
                return $this->returnError('Рецепт не найден или доступ к нему запрещен');
            }
        } else {
            $obRecipe = $this->recipeRepository->getEmpty($this->getTUser());
        }

        $obRecipe->makeRestrict($this->getTUser());

        return $this->json($obRecipe);
    }

    /**
     * @param Request $obRequest
     * @Route("/app/recipes/copy")
     *
     * @return Response
     */
    public function copy(Request $obRequest): Response
    {
        if ($intId = $obRequest->get('id')) {
            $obRecipe = $this->recipeRepository->getVisibleByID($intId, $this->getTUser());
            if (!$obRecipe) {
                return $this->returnError('Рецепт не найден или доступ к нему запрещен');
            }
        } else {
            return $this->returnError('Рецепт не найден или доступ к нему запрещен');
        }

        try {
            $obNewRecipe = clone $obRecipe;

            $obNewRecipe
                ->setName('Копия ' . $obRecipe->getName())
                ->setAuthor($this->getTUser())
                ->setXmlId('')
                ->setAccess('P');

            $obEntityManager = $this->getDoctrine()->getManager();

            $obEntityManager->persist($obNewRecipe);
            $obEntityManager->flush();

            foreach ($obRecipe->getIngredients() as $obRecipeIngredient) {
                $obNewRecipeIngredient = clone $obRecipeIngredient;
                $obNewRecipeIngredient->setRecipe($obNewRecipe);
                $obEntityManager->persist($obNewRecipeIngredient);
                $obEntityManager->flush();
            }

            $obEntityManager->refresh($obNewRecipe);
        } catch (Exception $obException) {
            return $this->returnException($obException);
        }

        return $this->json([
            'status'  => true,
            'message' => 'Скопировано',
            'item'    => $obNewRecipe
        ]);
    }

    /**
     * @param Request $obRequest
     * @Route("/app/recipes/add")
     *
     * @return Response
     */
    public function add(Request $obRequest): Response
    {

        $arResult = [
            'status'  => true,
            'message' => 'Сохранено'
        ];

        try {
            $arResult['item'] = $this->recipeRepository->put([
                'id'          => $obRequest->get('id') ?: 0,
                'name'        => $obRequest->get('name'),
                'tags'        => $obRequest->get('tags'),
                'ingredients' => $obRequest->get('ingredients'),
                'anounce'     => $obRequest->get('anounce'),
                'type'        => $obRequest->get('type'),
                'days'        => $obRequest->get('days'),
                'kkal'        => $obRequest->get('kkal'),
                'totalTime'   => $obRequest->get('totalTime'),
                'activeTime'  => $obRequest->get('activeTime'),
                'serving'     => $obRequest->get('serving'),
                'difficult'   => $obRequest->get('difficult'),
                'stages'      => $obRequest->get('stages')
            ], $this->getTUser())->makeRestrict($this->getTUser());
        } catch (Exception $eException) {
            $arResult['status']  = false;
            $arResult['message'] = $eException;
        }

        return $this->json($arResult);
    }

    /**
     * @param Request $obRequest
     * @Route("/app/recipes/publish")
     *
     * @return Response
     */
    public function publish(Request $obRequest): Response
    {
        return $this->makePublish($obRequest);
    }


    /**
     * @param Request $obRequest
     * @Route("/app/recipes/delete")
     *
     * @return Response
     */
    public function delete(Request $obRequest): Response
    {
        return $this->makeDelete($obRequest);
    }

    /**
     * @param Request $obRequest
     *
     * @Route("/app/autocomplete/recipe")
     * @return Response
     */
    public function autocomplete(Request $obRequest): Response
    {

        $arIngredients = $this->recipeRepository->findByName('%' . $obRequest->get('search') . '%', $this->getTUser());

        return $this->json($arIngredients);
    }
}
