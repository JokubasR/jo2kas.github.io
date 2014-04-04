<?php
/**
 * @author   Jokūbas Ramanauskas
 * @since    3/30/14
 */

namespace Controller;


use Logic\DataModel;
use Logic\PointsModel;

class IndexController extends BaseController
{
    /** @var \Logic\DataModel */
    private $_dataModel;

    /** @var \Logic\PointsModel */
    private $_pointsModel;

    public function __construct()
    {
        $this->_dataModel = new DataModel();
        $this->_pointsModel = new PointsModel();
    }

    public function defaultAction()
    {
        $stages     = $this->_dataModel->getGrandPrixs();
        $teams      = $this->_dataModel->getTeams();
        $engines    = $this->_dataModel->getEngines();
        $drivers    = $this->_dataModel->getDrivers();

        $userTeam = array();

        if (isset($_COOKIE['team'])) {
            $userTeam = unserialize($_COOKIE['team']);
        }

        return $this->render('index', array(
            'stages'    => $stages,
            'teams'     => $teams,
            'engines'   => $engines,
            'drivers'   => $drivers,
            'userTeam'  => $userTeam,
        ));
    }

    public function pointsAction()
    {
        if (!empty($_POST)) {
            try {
                $points = $this->_pointsModel->calculatePoints($_POST);
                setcookie('team', serialize($_POST), 0, '/');
            } catch(\Exception $exception) {
                $points = array();
            }
        }

        die( $this->renderAjax('points', array(
            'results'    => $points,
        )));
    }

    public function bestTeamAction()
    {
        if (!empty($_POST)) {
            try {
                $bestTeam = $this->_pointsModel->getBestTeam($_POST['stage']);
                $bestTeam['stage']  = $this->_dataModel->getGrandPrixTitle($bestTeam['stage']);
            } catch (\Exception $exception) {
                $bestTeam = array();
            }
        }

        die($this->renderAjax('best-team', array(
            'bestTeam' => $bestTeam,
        )));
    }
} 