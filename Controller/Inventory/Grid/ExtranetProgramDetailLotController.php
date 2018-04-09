<?php

namespace App\Controller\Inventory\Grid;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

use App\Entity\{
    Lot
};

use App\Controller\Inventory\{
    LotController, ProgramController
};

// prefix route with /grid/ name controller

/**
 * @Route("/grid/extranet_program_detail_lot")
 */

// convention name : route name where jqgrid is display concat extends controller
// ex : route name extranet_program_detail + extends lot => Class ExtranetProgramDetailLotController
// ex uri : /grid/extranet_program_detail_lot/get/data
class ExtranetProgramDetailLotController extends LotController
{

    const CURRENT_ROUTE = "extranet_program_detail_grid_lot";

    private $toJSON_M;
    private $toJSON_D;

    /**
     * @Route("/get/{type}", requirements={"type": "model|data"}, name="extranet_program_detail_grid_lot")
     * @IsGranted("ROLE_ADMIN")
     */

    public function getAction(Request $request, string $type)
    {

        switch ($type) {

            case 'model':
                $this->getFillGrid();
                return $this->json($this->toJSON_M);

            case 'data':

                $lots = $this->getDoctrine()->getRepository(Lot::class);

                $gridParam = $this->container->getParameter("grid");
                $limit = ($request->request->get('rows') ? $request->request->get('rows') : $gridParam['default_limit']);

                $resultat = $lots->findBySearchQuery(
                    $request->get('_route'),
                    $request->request->get('filters'),
                    $request->request->get('page'),
                    $request->request->get('sidx'),
                    $request->request->get('sord'),
                    $limit
                );

                $this->toJSON_D = (object)[];
                $this->toJSON_D->total = (($resultat["totalResult"] > 0) ? @ceil($resultat["totalResult"] / $limit) : 0);
                $this->toJSON_D->page = (
                    ( isset($resultat["pagination"]["page"]))
                    && ($resultat["pagination"]["page"] > $this->toJSON_D->total) ? $this->toJSON_D->total : $resultat["pagination"]["page"]);
                $this->toJSON_D->records = $resultat["totalResult"];

                if ($resultat["totalResult"]) {

                    foreach ($resultat["contenu"] as $id => $row) {
                        $this->getFillGrid( $id, $row);
                    }
                }


                return $this->json($this->toJSON_D);
        }

        return $this->json('{}');
    }

    public function getFillGrid( $id = null, $row = null){
        $em = $this->getDoctrine()->getManager();
        $conn = $em->getConnection();

        $this->toJSON_M = [];
        $this->toJSON_M[] = [
            'label' => 'id',
            'align' => 'center',
            'name' => 'idlot',
            'hidden' => true
        ];
        $this->toJSON_D->rows[$id]['id'] = $id;
        $this->toJSON_D->rows[$id]['cell'] = [];
        $this->toJSON_D->rows[$id]['cell'][] = $row['idlot'];

        $this->toJSON_M[] = [
            'label' => 'Numéro',
            'align' => 'center',
            'name' => 'loNumber',
            //'width' => 50
        ];

        $this->toJSON_D->rows[$id]['cell'][] = $row['loNumber'];





    }




    public function jQgrid(ProgramController $program)
    {

        $modelUrl = $program->get('router')->generate( self::CURRENT_ROUTE, ['type' => 'model']);
        $dataUrl = $program->get('router')->generate( self::CURRENT_ROUTE, ['type' => 'data']);

        $js = <<<JS

            var colModel = null;

            jQuery.getJSON('$modelUrl', function (colModel) {
                
                $("#extranet_program_detail_grid_lot").jqGrid({
                    url: "$dataUrl",
                    mtype: "POST",
                    //styleUI: 'jQueryUI',
                    datatype: "json",
                    colModel: colModel,
                    viewrecords: true,
                    height: 'auto',
                    autowidth: true,
                    //shrinkToFit: 300,
                    rowNum: 50,
                    rowList: [50, 100, 200],
                    pager: "#pager_extranet_program_detail_grid_lot",
                    toppager: true  
                });

                jQuery("#extranet_program_detail_grid_lot").jqGrid('navGrid',
                '#pager_extranet_program_detail_grid_lot',
                {edit:false,add:false,del:false,search:false,refresh:true, cloneToTop: true},
                {height: 200, reloadAfterSubmit: true}

                );

            });
            

            // Add responsive to jqGrid
            $(window).bind('resize', function () {
                var width = $('.jqGrid_wrapper').width();
                $('#extranet_program_detail_grid_lot').setGridWidth(width);
            });

            setTimeout(function(){
                $('.wrapper-content').removeClass('animated fadeInRight');
            },700);
    
JS;


        return $js;
    }

}
