<?php

namespace MWS\MoonManagerBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use MWS\MoonManagerBundle\Entity\MwsOffer;
use MWS\MoonManagerBundle\Entity\MwsOfferStatus;
use MWS\MoonManagerBundle\Entity\MwsTimeTag;
use MWS\MoonManagerBundle\Form\MwsSurveyJsType;
use Symfony\Component\Form\FormInterface;
use Twig\Environment as TwigEnv;
use Symfony\Component\Form\FormFactoryInterface;

/**
 * @extends ServiceEntityRepository<MwsOffer>
 *
 * @method MwsOffer|null find($id, $lockMode = null, $lockVersion = null)
 * @method MwsOffer|null findOneBy(array $criteria, array $orderBy = null)
 * @method MwsOffer[]    findAll()
 * @method MwsOffer[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MwsOfferRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
        protected TwigEnv $twig,
        protected FormFactoryInterface $formFactory,
        protected MwsOfferStatusRepository $mwsOfferStatusRepository,
        protected MwsTimeTagRepository $mwsTimeTagRepository,
    ) {
        parent::__construct($registry, MwsOffer::class);
    }

    public function fetchMwsAddOfferForm($offer = null)
    {
        // dd($this->container);
        // $tagSlugSep = '|'; // TODO :load objects and trick display/value function of surveyJS allowing '|' separator instead... ?
        $tagSlugSep = ' > ';
        $allSourceNames = $this
            ->createQueryBuilder("o")
            ->select("
                DISTINCT
                o.sourceName as value,
                o.sourceName as label
            ")
            ->where("o.sourceName IS NOT NULL")
            // TODO : 'value as label' not working. Why and do tips below...
            ->orderBy('o.sourceName', 'ASC')
            ->getQuery()->getResult();
        $allClientNames = $this
            ->createQueryBuilder("o")
            ->select("
                DISTINCT
                o.clientUsername as value,
                o.clientUsername as label
            ")
            ->where("o.clientUsername IS NOT NULL")
            ->orderBy('o.clientUsername', 'ASC')
            ->getQuery()->getResult();
        $allOfferTags = array_map(
            function (array $tagResp) use ($tagSlugSep) {
                $tag = $tagResp[0];
                $slug = $tag->getCategorySlug() . $tagSlugSep . $tag->getSlug();
                // dd($slug);
                return $slug;
                // return [
                //   "value" => $slug,
                //   "label" => $tag->getCategorySlug() . " > " . $tag->getSlug(),
                // ];
            },
            // $mwsOfferStatusRepository->findAll()
            $this->mwsOfferStatusRepository
                ->createQueryBuilder("s")
                // https://stackoverflow.com/questions/8233746/concatenate-with-null-values-in-sql
                ->select("s, CONCAT(COALESCE(s.categorySlug, ''), s.slug) AS slugKey")
                // ->where($qb->expr()->isNotNull("t.categorySlug"))
                // ->orderBy("t.categorySlug")
                // ->addOrderBy("t.slug")
                ->addOrderBy("slugKey")
                ->getQuery()->getResult()
        );
        // dd($allClientNames);
        $allOfferBudgets = $this
            ->createQueryBuilder("o")
            ->select("
                DISTINCT o.budget as value
            ")
            ->where("o.clientUsername IS NOT NULL")
            ->orderBy('o.budget', 'ASC')
            ->getQuery()->getResult();

        $tagQb = $this->mwsTimeTagRepository->createQueryBuilder("t")
            ->orderBy('LOWER(t.slug)');
        $timingTags = $tagQb->getQuery()->getResult();
        $allTimingTags = array_map(
            function (MwsTimeTag $tag) {
                return $tag->getSlug();
            },
            $timingTags
        );

        $mwsAddOfferConfig = [
            // TODO : serialize offer ?
            "jsonResult" => rawurlencode($offer ? json_encode($offer) : '{}'),
            "surveyJsModel" => rawurlencode($this->renderView(
                "@MoonManager/survey_js_models/MwsOfferAddType.json.twig",
                [
                    'allSourceNames' => $allSourceNames,
                    'allClientNames' => $allClientNames,
                    'allOfferTags' => $allOfferTags,
                    'allOfferBudgets' => $allOfferBudgets,
                    'allTimingTags' => $allTimingTags,
                ]
            )),
        ]; // TODO : save in session or similar ? or keep GET system data transfert system ?
        $mwsAddOfferForm = $this->createForm(MwsSurveyJsType::class, $mwsAddOfferConfig);
        return $mwsAddOfferForm;
    }

    // TODO doc : should use this function.... instead of entity->addTag....
    public function addTag($offer, $tag)
    {
        // TODO or TIPS ? : do in some Doctrine postpersiste listener ? well, no event on addTag right ?
        $em = $this->getEntityManager();
        /** @var MwsOfferStatusRepository */
        $tagsRepo =  $em->getRepository(MwsOfferStatus::class);
        if ($tag->getCategorySlug()) {
            $category = $tagsRepo->findOneWithSlugAndCategory(
                $tag->getCategorySlug(),
                null // TIPS : OK since only ONE LEVEL of category
            );
            if ($category && !$category->isCategoryOkWithMultiplesTags()) {
                // dd($offer->getTags());
                // Avoid tags duplicate if category avoid multiples, clean up first
                $tagsToRemove = [];
                foreach ($offer->getTags() as $existingTag) {
                    if (
                        $tag->getCategorySlug()
                        === $existingTag->getCategorySlug()
                    ) {
                        $tagsToRemove[] = $existingTag;
                    }
                }
                foreach ($tagsToRemove as $t) {
                    // $offer->getTags()->remove($id);
                    $offer->removeTag($t);
                }
            }
        }
        $offer->addTag($tag);
    }

    // TODO : doc : will return ALL category and sub category tags
    public function getExtendedTags($offer)
    {
        $extendedTags = [];
        $tags = $offer->getTags();

        $em = $this->getEntityManager();
        /** @var MwsOfferStatusRepository */
        $tagsRepo =  $em->getRepository(MwsOfferStatus::class);

        foreach ($tags as $tag) {
            $extendedTags[] = $tag;
            if ($tag->isCategoryOkWithMultiplesTags()) {
                // TODO : more like a repository function, need to seek tags...
                $extendedTags[] = $tagsRepo->findBy([
                    'categorySlug' => $tag->getCategorySlug()
                ]);
            }
        }
    }

    /**
     * Creates and returns a Form instance from the type of the form.
     */
    protected function createForm(string $type, mixed $data = null, array $options = []): FormInterface
    {
        // An exception has been thrown during the rendering of a template 
        // ("The "form.factory" service or alias has been removed or inlined 
        // when the container was compiled. You should either make it public,
        // or stop using the container directly and use dependency injection instead.").
        // return $this->container->get('form.factory')->create($type, $data, $options);
        // php bin/console debug:container | grep 'form.factory'
        return $this->formFactory->create($type, $data, $options);
    }

    /**
     * Returns a rendered view.
     *
     * Forms found in parameters are auto-cast to form views.
     */
    protected function renderView(string $view, array $parameters = []): string
    {
        // TODO : container is missing twig ?
        // if (!$this->container->has('twig')) {
        //     throw new \LogicException('You cannot use the "renderView" method if the Twig Bundle is not available. Try running "composer require symfony/twig-bundle".');
        // }

        foreach ($parameters as $k => $v) {
            if ($v instanceof FormInterface) {
                $parameters[$k] = $v->createView();
            }
        }

        // return $this->container->get('twig')->render($view, $parameters);
        return $this->twig->render($view, $parameters);
    }

    //    /**
    //     * @return MwsOffer[] Returns an array of MwsOffer objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('m')
    //            ->andWhere('m.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('m.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?MwsOffer
    //    {
    //        return $this->createQueryBuilder('m')
    //            ->andWhere('m.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
