<?php

namespace App\Entity\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\Query;

use App\DoctrineExtension\SqlCalcFoundRows;

use App\Entity\{
    Lot
};

class LotRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Lot::class);
    }


    public function findBySearchQuery(string $routeName, ?string $filters, ?int $page, ?string $sidx, ?string $sord, ?int $limit): array
    {

        $pagination = [
            'page' => $page,
            'borneInf' => (int)($page ? ($limit * ($page - 1)) : 0),
            'intervalle' => (int)$limit
        ];

        $resultat = [
            "pagination" => $pagination,
            "totalResult" => 0,
            "reussi" => false,
            "contenu" => [],
            "retour" => ""
        ];


        if ($filters) {
            $filters = json_decode($filters);
            // @todo inspect POST from jqgrid and complete query
        }


        $params = [];
        $query = $this->getEntityManager();
        $conn = $query->getConnection();

        $qb = $query->createQueryBuilder();
        $qb->select('l');
        $qb->from('App:Lot', 'l');


        switch ($routeName) {
            case 'extranet_program_detail_grid_lot':

                $qb->where('l.loNumber LIKE :lo_number ');
                $value = '%a%';
                $qb->setParameter('lo_number', $value);
                break;
            default:
                // we send empty tab
                // if we have no route
                return $resultat;

        }


        if ($sidx && $sord) {
            $qb->orderby($sidx, $sord);
        }

        // warning order is important
        // getQuery must be call before setHint !
        $query = $qb->getQuery();
        $query->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER,'App\DoctrineExtension\SqlCalcFoundRows');
        $query->setHint("mysqlWalker.sqlCalcFoundRows", true);


        $success = $query->execute();
        $contenu = $query->getResult(\PDO::FETCH_ASSOC);


        $resultat['totalResult'] = $conn->query('SELECT FOUND_ROWS()')->fetch(\PDO::FETCH_COLUMN);
        $resultat['reussi'] = $success;
        $resultat['contenu'] = $contenu;
        $resultat['pagination'] = $pagination;

        return $resultat;
    }

    private function sanitizeSearchQuery(string $query): string
    {
        return trim(preg_replace('/[^A-Za-z0-9\_]/', ' ', $query));

    }


}
