<?php
// 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com

/*
# Display info about twig :
php bin/console debug:twig
# Display info about specific filter :
php bin/console debug:twig --filter=price
*/

// Biblio :
// https://symfony.com/doc/5.3/templating/twig_extension.html
namespace MWS\MoonManagerBundle\Twig;

use MWS\MoonManagerBundle\Form\MwsSurveyJsType;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Twig\TwigFilter;
// use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\Container;
// use Symfony\Component\Form\FormFactory;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Twig\TwigFunction;
use Twig\Environment as TwigEnv;

class EnvExtension extends AbstractExtension implements GlobalsInterface
{
  protected Container $container;

  public function __construct(
    // protected ContainerInterface $container
    protected KernelInterface $kernel,
    protected TwigEnv $twig,
    protected FormFactoryInterface $formFactory,
  ) {
    // https://stackoverflow.com/questions/45965327/symfony-how-to-get-container-in-my-service
    // $this->container = $kernel->getContainer();
    // dd($this->container);
  }

  public function getFilters()
  {
    return [
      new TwigFilter('humanSize', [$this, 'humanSize']),
      new TwigFilter('bytesSize', [$this, 'bytesSize']),
      new TwigFilter('iteratorToArray', [$this, 'iteratorToArray']),

      // new TwigFilter('price', [$this, 'formatPrice']),
    ];
  }

  public function iteratorToArray($it)
  {
    return iterator_to_array($it);
  }

  public function humanSize($size)
  {
    $size = intval($size);
    // Then, humanize :
    if ($size < 1024) {
      $size = $size . " Bytes";
    } elseif (($size < 1048576) && ($size > 1023)) {
      $size = round($size / 1024, 1) . " KB";
    } elseif (($size < 1073741824) && ($size > 1048575)) {
      $size = round($size / 1048576, 1) . " MB";
    } else {
      $size = round($size / 1073741824, 1) . " GB";
    }
    return $size;
  }

  public function bytesSize($size)
  {
    // dump($size);
    // $sizeParts = explode(' ', $size);
    $m = [];
    $size = str_replace(' ', '', $size);
    preg_match('/([0-9]+)([^0-9]*)/', $size, $m);
    // $sizeInt = intval(substr($size, );
    $intPart = intval($m[1] ?? 0);
    $factor = [
      'bytes' => 1,
      'kb' => 1023,
      'mb' => 1048575,
      'gb' => 1073741824,
      // https://canada.lenovo.com/fr/ca/en/glossary/kilobyte/
      // Bien que les kilooctets (Ko) et les kilobits (Ko) soient 
      // des unités de l'information numérique, elles mesurent
      // différents aspects. Les kilooctets mesurent la
      // capacité de stockage ou la taille des données,
      // tandis que les kilobits mesurent le débit de 
      // transfert ou la vitesse de transmission des données.
      'ko' => 1023,
      'mo' => 1048575,
      'go' => 1073741824,
    ][strtolower(trim($m[2] ?? 'Bytes'))];
    // dump($m);
    // dump($intPart);
    // dd($factor);
    return $intPart * $factor;
  }

  // public function formatPrice($number, $decimals = 0, $decPoint = '.', $thousandsSep = ',')
  // {
  //     $price = number_format($number, $decimals, $decPoint, $thousandsSep);
  //     $price = '$'.$price;

  //     return $price;
  // }


  public function getFunctions()
  {
      return [
        new TwigFunction('fetchMwsAddOfferConfig', [$this, 'fetchMwsAddOfferConfig']),
        // new TwigFunction('area', [$this, 'calculateArea']),
      ];
  }

  // public function calculateArea(int $width, int $length)
  // {
  //     return $width * $length;
  // }

  public function fetchMwsAddOfferConfig() {
    $this->container = $this->kernel->getContainer();
    // dd($this->container);

    $mwsAddOfferConfig = [
      "jsonResult" => rawurlencode(json_encode([
        // "searchKeyword" => $keyword,
      ])),
      "surveyJsModel" => rawurlencode($this->renderView(
        "@MoonManager/survey_js_models/MwsOfferAddType.json.twig",
        [
          // "availableTemplates" => $availableTemplates,
          // "availableTemplateNameSlugs" => $availableTemplateNameSlugs,
          // "availableMonwooAmountType" => $availableMonwooAmountType,
          // "availableTemplateCategorySlugs" => $availableTemplateCategorySlugs,
        ]
      )),
    ]; // TODO : save in session or similar ? or keep GET system data transfert system ?
    $mwsAddOfferForm = $this->createForm(MwsSurveyJsType::class, $mwsAddOfferConfig);

    return $mwsAddOfferForm->createView();
  }

  // Lazy load :
  // public function getFilters()
  // {
  //     return [
  //         // the logic of this filter is now implemented in a different class
  //         new TwigFilter('price', [AppRuntime::class, 'formatPrice']),
  //     ];
  // }

  public function getGlobals(): array
  {
    // $fetchMwsAddOfferConfig = function () {
    //   $this->container = $this->kernel->getContainer();
    //   dd($this->container);
  
    //   $mwsAddOfferConfig = [
    //     "jsonResult" => rawurlencode(json_encode([
    //       // "searchKeyword" => $keyword,
    //     ])),
    //     "surveyJsModel" => rawurlencode($this->renderView(
    //       "@MoonManager/survey_js_models/MwsMessageType.json.twig",
    //       [
    //         // "availableTemplates" => $availableTemplates,
    //         // "availableTemplateNameSlugs" => $availableTemplateNameSlugs,
    //         // "availableMonwooAmountType" => $availableMonwooAmountType,
    //         // "availableTemplateCategorySlugs" => $availableTemplateCategorySlugs,
    //       ]
    //     )),
    //   ]; // TODO : save in session or similar ? or keep GET system data transfert system ?
    //   $mwsAddOfferForm = $this->createForm(MwsSurveyJsType::class, $mwsAddOfferConfig);  
    // };

    return [
      // 'fetchMwsAddOfferConfig' => $fetchMwsAddOfferConfig, // Nop, use set function instead...
      'mwsTimingLookupFields' => [
        // TODO : dynamic from surveyJS json fields instead of ram list + reuse for twig... ? or from backend configs ?
        'searchKeyword' => true,
        "searchStart" => true,
        "searchEnd" => true,
        'searchTags' => true,
        'searchTagsToInclude' => true,
        'searchTagsToAvoid' => true,
      ],
    ];
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
}
