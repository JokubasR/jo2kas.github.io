<?php
/**
 * @author   Jokūbas Ramanauskas
 * @since    3/31/14
 */

namespace Logic;

/**
 * Class PointsModel
 * @package Logic
 */
class PointsModel
{
    const TYPE_QUALIFYING   = 'qualifying';
    const TYPE_RACE         = 'race';

    const POINTS_MULTIPLIER_DRIVER  = 1;
    const POINTS_MULTIPLIER_TEAM    = 0.8;
    const POINTS_MULTIPLIER_ENGINE  = 0.2;

    /** @var DataModel */
    private $_dataModel;

    public function __construct()
    {
        $this->_dataModel = new DataModel();
    }

    /**
     * Place => points
     * @var array
     */
    private $pointsQualifying = array(
        1   => 10,
        2   => 8,
        3   => 6,
        4   => 5,
        5   => 4,
        6   => 3,
        7   => 2,
        8   => 1,
    );

    /**
     * Place => points
     * @var array
     */
    private $pointsRace = array(
        1   => 25,
        2   => 18,
        3   => 15,
        4   => 12,
        5   => 10,
        6   => 8,
        7   => 6,
        8   => 5,
        9   => 4,
        10  => 3,
        11  => 2,
        12  => 1,
    );

    /**
     * @param $position
     *
     * @return int
     */
    public function getQualifyingPoints($position)
    {
        return array_key_exists($position, $this->pointsQualifying)
                ? $this->pointsQualifying[$position]
                : 0;
    }

    /**
     * @param $position
     *
     * @return int
     */
    public function getRacePoints($position)
    {
        return array_key_exists($position, $this->pointsRace)
            ? $this->pointsRace[$position]
            : 0;
    }

    /**
     * Returns calculated points for both events
     *
     * @param       $data
     * @param array $qualifyingResults
     * @param array $raceResults
     * @param null  $stageTitle
     *
     * @return array
     */
    public function calculatePoints($data, $qualifyingResults = array(), $raceResults = array(), $stageTitle = null)
    {
        $qualifyingResults  = empty($qualifyingResults)
                                ? $this->_dataModel->getQualifyingResults($data['stage'])
                                : $qualifyingResults;
        $raceResults        = empty($raceResults)
                                ? $this->_dataModel->getResults(null, null, $data['stage'])
                                : $raceResults;

        $points = array();
        $totalPoints = 0;

        if (!empty($qualifyingResults)) {
            $points += $this->pointCalculateMacro($qualifyingResults, $data, self::TYPE_QUALIFYING);
            $totalPoints += !empty($points[self::TYPE_QUALIFYING]['totalPoints'])
                    ? $points[self::TYPE_QUALIFYING]['totalPoints']
                    : 0;
        }

        if (!empty($raceResults)) {
            $points += $this->pointCalculateMacro($raceResults, $data, self::TYPE_RACE);
            $totalPoints += !empty($points[self::TYPE_RACE]['totalPoints'])
                    ? $points[self::TYPE_RACE]['totalPoints']
                    : 0;
        }

        return array(
            'points' => $points,
            'stage'  => null === $stageTitle
                        ? $this->_dataModel->getGrandPrixTitle($data['stage'])
                        : $stageTitle,
            'totalPoints'   => $totalPoints,
        );
    }

    /**
     * Calculates given type results
     *
     * @param $data
     * @param $team
     * @param $type
     *
     * @return array
     */
    private function pointCalculateMacro($data, $team, $type)
    {
        $pilots = array(
            'pilot1'    => $team['pilot1'],
            'pilot2'    => $team['pilot2'],
        );

        $points = array(
            $type => array(
                'totalPoints'   => 0,
                'points' => array(
                    'team'      => 0,
                    'engine'    => 0,
                    'pilot1'    => 0,
                    'pilot2'    => 0,
                ),
            ),
        );

        foreach ($data as $result) {

            foreach ($pilots as $key => $pilot) {
                if ($result['driverId'] === $pilot) {
                    $assignPoints = $this->getPoints($type, $result['position']) * self::POINTS_MULTIPLIER_DRIVER;
                    $points[$type]['points'][$key] = $assignPoints;
                    $points[$type]['totalPoints'] += $assignPoints;
                }
            }

            if ($this->_dataModel->getTeamFromResultData($result['team']) === $team['team']) {
                $assignPoints = $this->getPoints($type, $result['position']) * self::POINTS_MULTIPLIER_TEAM;
                $points[$type]['points']['team'] += $assignPoints;
                $points[$type]['totalPoints'] += $assignPoints;

            }

            if ($this->_dataModel->getEngineFromResultData($result['team']) === $team['engine']) {
                $assignPoints = $this->getPoints($type, $result['position']) * self::POINTS_MULTIPLIER_ENGINE;
                $points[$type]['points']['engine'] += $assignPoints;
                $points[$type]['totalPoints'] += $assignPoints;
            }
        }

        return $points;
    }

    private function getPoints($type, $position)
    {
        switch ($type) {
            case self::TYPE_QUALIFYING:
                return $this->getQualifyingPoints($position);
            break;
            case self::TYPE_RACE:
                return $this->getRacePoints($position);
            break;
        }
    }

    public function getBestTeam($stageUrl)
    {
        $qualifyingResults = $this->_dataModel->getQualifyingResults($stageUrl);
        $raceResults       = $this->_dataModel->getResults(null, null, $stageUrl);

        $drivers    = $this->_dataModel->getDrivers();
        $drivers2   = $drivers;
        $teams      = $this->_dataModel->getTeams();
        $engines    = $this->_dataModel->getEngines();

        $bestTeam = array();

        if (!empty($qualifyingResults) && !empty($raceResults)) {

            foreach ($drivers as $team1pilots) {
                foreach ($team1pilots as $pilot1) {
                    foreach ($drivers2 as $team2pilots) {
                        foreach ($team2pilots as $pilot2) {
                            if ($pilot1 === $pilot2) {
                                continue;
                            }
                            foreach ($teams as $team) {
                                foreach ($engines as $engine => $engineTeams) {
                                    $currentTeam = array(
                                        'team'  => array(
                                            'pilot1' => $pilot1['driverId'],
                                            'pilot2' => $pilot2['driverId'],
                                            'team'   => $team['title'],
                                            'engine' => $engine,
                                        ),
                                        'stage'  => $stageUrl,
                                    );
                                    $point = $this->calculatePoints($currentTeam['team'], $qualifyingResults, $raceResults, 'BEST TEAM');

                                    if (empty($bestTeam)) {
                                        $bestTeam = $currentTeam + array('points' => $point['totalPoints']);
                                    }
                                    if ($point['totalPoints'] > $bestTeam['points']) {
                                        $bestTeam = $currentTeam + array('points' => $point['totalPoints']);                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        $bestTeam['team']['pilot1'] = $this->_dataModel->findDriverTitleById($bestTeam['team']['pilot1'], $drivers);
        $bestTeam['team']['pilot2'] = $this->_dataModel->findDriverTitleById($bestTeam['team']['pilot2'], $drivers);

        return $bestTeam;
    }
} 