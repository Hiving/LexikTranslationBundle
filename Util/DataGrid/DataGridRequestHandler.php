<?php

namespace Lexik\Bundle\TranslationBundle\Util\DataGrid;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use Lexik\Bundle\TranslationBundle\Document\TransUnit as TransUnitDocument;
use Lexik\Bundle\TranslationBundle\Manager\TransUnitManagerInterface;
use Lexik\Bundle\TranslationBundle\Storage\StorageInterface;

/**
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class DataGridRequestHandler
{
    /**
     * @var TransUnitManagerInterface
     */
    protected $transUnitManager;

    /**
     * @var StorageInterface
     */
    protected $storage;

    /**
     * @var array
     */
    protected $managedLoales;

    /**
     * @param TransUnitManagerInterface $transUnitManager
     * @param StorageInterface          $storage
     * @param array                     $managedLoales
     */
    public function __construct(TransUnitManagerInterface $transUnitManager, StorageInterface $storage, array $managedLoales)
    {
        $this->transUnitManager = $transUnitManager;
        $this->storage = $storage;
        $this->managedLoales = $managedLoales;
    }

    /**
     * Returns an array with the trans unit for the current page and the total of trans units
     *
     * @param Request $request
     * @return array
     */
    public function getPage(Request $request)
    {
        $transUnits = $this->storage->getTransUnitList(
            $this->managedLoales,
            $request->query->get('rows', 20),
            $request->query->get('page', 1),
            $request->query->all()
        );

        $count = $this->storage->countTransUnits($this->managedLoales, $request->query->all());

        return array($transUnits, $count);
    }


    /**
     * Updates a trans unit from the request.
     *
     * @param integer $id
     * @param Request $request
     * @throws NotFoundHttpException
     * @return \Lexik\Bundle\TranslationBundle\Model\TransUnit
     */
    public function updateFromRequest($id, Request $request)
    {
        $transUnit = $this->storage->getTransUnitById($id);

        if (!$transUnit) {
            throw new NotFoundHttpException(sprintf('No TransUnit found for "%s"', $id));
        }

        $translationsContent = array();
        foreach ($this->managedLoales as $locale) {
            $translationsContent[$locale] = $request->request->get($locale);
        }

        $this->transUnitManager->updateTranslationsContent($transUnit, $translationsContent);

        if ($transUnit instanceof TransUnitDocument) {
            $transUnit->convertMongoTimestamp();
        }

        $this->storage->flush();

        return $transUnit;
    }
}
