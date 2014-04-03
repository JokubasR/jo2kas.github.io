<?php
/**
 * @author   Jokūbas Ramanauskas
 * @since    3/30/14
 */

namespace Logic;


class DataModel
{
    /** @var \Logic\Settings */
    private $_settings;

    private $xPathStages    = "//table[@class='raceResults']/tr/td[1]/a";

    private $xPathTeam      = "//div[@id='contentMain']//div[@class='indexContainer']";

    private $xPathResults       = "//table[@class='raceResults']/tr[position() > 1]";

    private $xPathQualifyingLink = "//li[@class='listheader' and contains(., 'QUALIFYING')]//a[contains(.,'QUALIFYING')]/@href";

    private $xPathDrivers   = "//ul[@class='driverMugShot']/li/div/p/a";

    private $xPathRaceTitle = "//div[@class='raceResultsHeading']/h2/text()";

    private $teamEngines    = array(
        'Mercedes' => array(
            'Mercedes',
            'McLaren',
            'Williams',
            'Force India',
        ),
        'Renault' => array(
            'Red Bull Racing',
            'Lotus',
            'Toro Rosso',
            'Caterham',
        ),
        'Ferrari' => array(
            'Ferrari',
            'Sauber',
            'Marussia',
        ),
    );

    public function __construct()
    {
        $this->_settings = new Settings();
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function getGrandPrixs()
    {
        $data = $this->getContent(Settings::URL_STAGES);

        $doc = new \DOMDocument();
        libxml_use_internal_errors(true);
        $doc->loadHTML($data);

        $xpath = new \DOMXPath($doc);

        $items = $xpath->query($this->xPathStages);

        if (empty($items) || ! $items->length > 0) {
            throw new \Exception('No results found', 202);
        }

        $result = array();
        for ($i = 0; $i < $items->length; $i++) {
            $item = $items->item($i);
            $result[$item->nodeValue] = array(
                'link'  => $item->attributes->getNamedItem('href')->nodeValue,
                'title' => $item->nodeValue,
            );
        }

        return $result;
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function getTeams()
    {
        $data = $this->getContent(Settings::URL_TEAMS);

        $doc = new \DOMDocument();
        libxml_use_internal_errors(true);
        $doc->loadHTML($data);

        $xpath = new \DOMXPath($doc);

        $items = $xpath->query($this->xPathTeam);

        if (empty($items) || ! $items->length > 0) {
            throw new \Exception('No results found', 202);
        }

        $result = array();

        for ($i = 0; $i < $items->length; $i++) {
            $item     = $items->item($i)->childNodes->item(1);
            $team     = $items->item($i)->childNodes->item(3);

            $result[] = array(
                'title' => $item->childNodes->item(1)->attributes->getNamedItem('alt')->nodeValue,
                'image' => $team->childNodes->item(1)->childNodes->item(1)->attributes->getNamedItem('src')->nodeValue,
                'members' => array(
                    array(
                        'title' => $item->childNodes->item(2)->attributes->getNamedItem('alt')->nodeValue,
                        'image' => $item->childNodes->item(2)->attributes->getNamedItem('src')->nodeValue,
                    ),
                    array(
                        'title' => $item->childNodes->item(3)->attributes->getNamedItem('alt')->nodeValue,
                        'image' => $item->childNodes->item(3)->attributes->getNamedItem('src')->nodeValue,
                    )
                ),
            );
        }

        return $result;
    }

    /**
     * @param string $stage
     * @param array  $stages
     * @param string $stageUrl
     *
     * @return array
     */
    public function getResults($stage = "", $stages = array(), $stageUrl = null)
    {
        if (empty($stages) && !empty($stage)) {
            $stages = $this->getGrandPrixs();
        }

        $stageUrl = null === $stageUrl
                        ? $stages[$stage]['link']
                        : $stageUrl;

        if (!empty($stageUrl)) {
            $data = $this->getContent(Settings::URL_HOST . $stageUrl);

            $doc = new \DOMDocument();
            libxml_use_internal_errors(true);
            $doc->loadHTML($data);

            $xpath = new \DOMXPath($doc);

            $items = $xpath->query($this->xPathResults);

            if (empty($items) || ! $items->length > 0) {
                return array();
            }

            $result = array();

            for ($i = 0; $i < $items->length; $i++) {
                $item = $items->item($i);

                $driverId = $item->childNodes->item(2)->nodeValue;

                $result[$driverId] = array(
                    'driverId'  => $driverId,
                    'position'  => $item->childNodes->item(0)->nodeValue,
                    'title'     => $item->childNodes->item(4)->firstChild->nodeValue,
                    'team'      => $item
                            ->childNodes
                            ->item(6)
                            ->nodeValue,
                );
            }

            return $result;
        }
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function getDrivers()
    {
        $data = $this->getContent(Settings::URL_DRIVERS);

        $doc = new \DOMDocument();
        libxml_use_internal_errors(true);
        $doc->loadHTML($data);

        $xpath = new \DOMXPath($doc);

        $items = $xpath->query($this->xPathDrivers);

        if (empty($items) || ! $items->length > 0) {
            throw new \Exception('No results found', 202);
        }

        $result = array();

        for ($i = 0; $i < $items->length; $i++) {
            $item = $items->item($i);

            $driverData = $item->childNodes->item(0)->nodeValue;

            $team = $item->childNodes->item(1)->nodeValue;

            $driverData = explode(' ', trim($driverData));

            $driverId = array_shift($driverData);
            $result[$team][$driverId] = array(
                'driverId'  => $driverId,
                'title'     => implode(' ', $driverData),
                'team'      => $team,
            );
        }

        return $result;
    }

    /**
     * @param $raceUlr
     *
     * @return array
     * @throws \Exception
     */
    public function getQualifyingResults($raceUlr)
    {
        /*
         * Get qualifying link
         */

        $data = $this->getContent(Settings::URL_HOST . $raceUlr);

        $doc = new \DOMDocument();
        libxml_use_internal_errors(true);
        $doc->loadHTML($data);

        $xpath = new \DOMXPath($doc);

        $items = $xpath->query($this->xPathQualifyingLink);

        if (empty($items) || ! $items->length > 0) {
            throw new \Exception('No results found', 202);
        }

        $qualifyingLink = $items->item(0)->nodeValue;

        /*
         * Get qualifying results
         */

        return $this->getResults(null, null, $qualifyingLink);
    }

    public function getGrandPrixTitle($stageUrl)
    {
        $data = $this->getContent(Settings::URL_HOST . $stageUrl);

        $doc = new \DOMDocument();
        libxml_use_internal_errors(true);
        $doc->loadHTML($data);

        $xpath = new \DOMXPath($doc);

        $items = $xpath->query($this->xPathRaceTitle);

        if (empty($items) || !$items->length > 0) {
            throw new \Exception('No results found', 202);
        }

        $grandPrixTitle = $items->item(0)->nodeValue;

        return $grandPrixTitle;
    }

    /**
     * @return array
     */
    public function getEngines()
    {
        return $this->teamEngines;
    }

    /**
     * Checks if team is assigned to given engine
     * @param $engine
     * @param $team
     *
     * @return bool
     */
    public function engineHasTeam($engine, $team)
    {
        return in_array($team, $this->teamEngines[$engine]);
    }

    /**
     * It could be:
     *  * Ferrari
     *  * Sauber-Ferrari
     *  * McLaren-Mercedes
     *  * Red Bull Racing-Renault
     * {team}-{engine}
     *
     * @param $teamTitle
     *
     * @return string
     */
    public function getEngineFromResultData($teamTitle)
    {
        $words = explode(' ', $teamTitle);

        $engine = array_pop($words);

        /*
         * It could happen, that $engine value is Racing-Renault (if RBR is the team).
         */
        if ($dashPosition = strpos($engine, '-')) {
            $engine = substr($engine, $dashPosition + 1);
        }

        return $engine;
    }

    /**
     * Same as getEngineFromResultData
     * Except that we need to strip the engine part
     *
     * @param $teamTitle
     *
     * @return mixed
     */
    public function getTeamFromResultData($teamTitle)
    {
        $team = explode('-', $teamTitle);
        return array_shift($team);
    }

    /**
     * Finds driver by his driverId
     *
     * @param       $driverId
     * @param array $drivers
     *
     * @return mixed
     */
    public function findDriverById($driverId, $drivers =  array())
    {
        $drivers = empty($drivers)
                    ? $this->getDrivers()
                    : $drivers;

        foreach ($drivers as $teamDrivers) {
            if (array_key_exists($driverId, $teamDrivers)) {
                return $teamDrivers[$driverId];
            }
        }

        return $driverId;
    }

    public function findDriverTitleById($driverId, $drivers =  array())
    {
        $data = $this->findDriverById($driverId, $drivers);

        if (is_array($data)) {
            return $data['title'];
        }

        return $data;
    }

    /**
     * @param $url
     *
     * @return string
     */
    private function getContent($url)
    {
        return file_get_contents($url);
    }


} 