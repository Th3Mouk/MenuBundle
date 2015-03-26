<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 23/02/15
 * Time: 22:07
 */

namespace Id4v\Bundle\MenuBundle\Twig;


use Id4v\Bundle\MenuBundle\Entity\MenuItem;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Routing\RouterInterface;

class MenuExtension extends \Twig_Extension {

    protected $doctrine;

    protected $router;

    function __construct(RegistryInterface $doctrine,RouterInterface $router)
    {
        $this->doctrine=$doctrine;
        $this->router=$router;
    }

    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction("render_menu",
              array(
                $this,
                'renderMenu'
              ),
              array(
                'is_safe' => array('html')
              )
            )
        );
    }

    /**
     * Render the menu given the machine_name
     * You can specify the markup to use in the options
     * Defaults:
     *  * rootOpen : <ul class="menu-list">
     *  * rootClose : </ul>
     *  * leafOpen : <li class="menu-item">
     *  * leafClose : </li>
     *  * linkClasses : menu-link
     * @param $machineName
     * @param $options
     * @return string
     */
    public function renderMenu($machineName,$options=array()){

        $newLine="\n";

        $rootOpen="<ul class='menu-list'>";
        $leafOpen="<li class='menu-item'>";
        $rootClose="</ul>";
        $leafClose="</li>";
        $linkClasses="menu-link";

        $html="";

        $depth=0;

        foreach($options as $key=>$value){
            $$key=$value;
        }


        $menu=$this->doctrine->getRepository("Id4vMenuBundle:Menu")->findOneBy(array("slug"=>$machineName));
        if(!$menu){
            return "";
        }
        $items=$menu->getHierarchy();

        /**
         * @var  $id int
         * @var  $item MenuItem
         */
        foreach($items as $id=>$item){
            //$depth = profondeur de l'item précédent
            // Si $depth == $item->getDepth => On est sur des frères, on ferme l'item précédent.
            // Si $depth+1 == $item->getDepth => On est sur des enfants, on ferme pas l'item précédent.
            // Si $depth-1 == $item->getDepth => On est sur un retour depuis les enfants, on ferme l'item précédent.

            if($item->getDepth() == $depth-1){
                $html.=$newLine.$leafClose;
                $html.=$newLine.$rootClose;
            }

            if($depth==$item->getDepth()){
                $html.=$newLine.$leafClose;
            }
            //Si depth = depth+1 on ouvre une racine
            if($item->getDepth() == $depth+1){
                $html.=$newLine.$rootOpen;
            }

            $url=$this->router->getContext()->getBaseUrl();
            if(strpos($item->getUrl(),"http://")!==false || strpos($item->getUrl(),"https://")!==false){
                $url=$item->getUrl();
            }else{
                $url.=$item->getUrl();
            }


            $html.=$newLine.$leafOpen.
              "<a target='".$item->getTarget()."' href='".$url."' class='".$linkClasses." menu-item-lvl-".$item->getDepth()."'>".$item->getTitle()."</a>";

            $depth=$item->getDepth();
        }

        for($i=$depth;$i>1;$i--){
            $html.=$newLine.$leafClose;
            $html.=$newLine.$rootClose;
        }

        $html.=$newLine.$leafClose;
        $html.=$newLine.$rootClose;

        return $html;
    }


    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return "menu_extension";
    }
}
