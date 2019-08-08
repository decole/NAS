<?php
namespace app\helpers\watering;

use app\helpers\mqtt\MqttLogic;
use app\helpers\telegram\TelegramLogic;
use app\models\Arduinoiot;
use app\models\Mqtt;
use Yii;
use yii\base\BaseObject;


/**
 * This class make logic on server from commanding Smart Watering
 */
class WateringLogic extends BaseObject
{
    /**
     * For Alice use
     */
    private $swiftMajor = 'water/major';
    private $swiftOne   = 'water/1';
    private $swiftTwo   = 'water/2';
    private $swiftThree = 'water/3';
    private $checkMajor = 'water/check/major';
    private $checkOne   = 'water/check/1';
    private $checkTwo   = 'water/check/2';
    private $checkThree = 'water/check/3';
    private $mqtt;

    public function __construct(array $config = [])
    {
        parent::__construct($config);
        $this->mqtt = new MqttLogic();

    }

    public function wateringState()
    {
        $major = $this->mqtt->getCacheMqtt($this->checkMajor);
        $one = $this->mqtt->getCacheMqtt($this->checkOne);
        $two = $this->mqtt->getCacheMqtt($this->checkTwo);
        $three = $this->mqtt->getCacheMqtt($this->checkThree);

        $string = '';

        if($major+$one+$two+$two+$three > 0) {
            $string .= 'Автополив сейчас работает.';
        }
        if($major+$one+$two+$two+$three == 0) {
            $string .= 'Автополив сейчас не работает.';
        }
        return $string . PHP_EOL;

    }

    public function wateringCheckMajor()
    {
        $options = [
            0 => $this->checkMajor, // главный клапан
            1 => $this->checkOne,   // клапан 1
            2 => $this->checkTwo,   // клапан 2
            3 => $this->checkThree  // клапан 3
        ];
        $request = [];
        foreach ($options as $key=>$topic) {
            $request[$key] = $this->mqtt->getCacheMqtt($topic);
        }

        if ($request[0] == 1 && (($request[1] + $request[2] + $request[3]) == 0)) {
            self::sendMessage('автополив.событие - только главный клапан включен');
            $this->MajorOff();
        }

    }

    public function swiftOn($topic): void
    {
        $mqtt = $this->mqtt;
        $options = $mqtt::listTopics();
        $mqtt->post($topic, $options[$topic]['condition']['on']);

    }

    public function swiftOff($topic): void
    {
        $mqtt = $this->mqtt;
        $options = $mqtt::listTopics();
        $mqtt->post($topic, $options[$topic]['condition']['off']);

    }

    /**
     * Stop all smart watering swifts
     */
    public function stopAll(): void
    {
        $this->swiftOff($this->swiftMajor);
        $this->swiftOff($this->swiftOne);
        $this->swiftOff($this->swiftTwo);
        $this->swiftOff($this->swiftThree);
        self::sendMessage('автополив - останов всего');

    }

    /**
     * Включение главного клапана
     */
    public function MajorOn(): void
    {
        $this->swiftOn($this->swiftMajor);
        self::sendMessage('автополив - главный клапан включен');

    }

    /**
     * Выключение главного клапана
     * !!! главный клапан отключается последним во всех цыклах
     */
    public function MajorOff(): void
    {
        $topics = [$this->swiftOne, $this->swiftTwo, $this->swiftThree, $this->swiftMajor];
        foreach ($topics as $topic) {
            sleep(0.3);
            $this->swiftOff($topic);
        }
        self::sendMessage('автополив - все клапана выключены');

    }

    /**
     * Включение клапана 1
     */
    public function OneOn(): void
    {
        $this->swiftOn($this->swiftMajor);
        sleep(0.2);
        $this->swiftOn($this->swiftOne);
        sleep(0.4);
        $this->swiftOff($this->swiftTwo);
        sleep(0.2);
        $this->swiftOff($this->swiftThree);
        self::sendMessage('автополив - клапан 1 включен');

    }

    /**
     * Выключение клапана 1
     */
    public function OneOff(): void
    {
        $this->swiftOff($this->swiftOne);

    }


    /**
     * Включение клапана 2
     */
    public function TwoOn(): void
    {
        $this->swiftOn($this->swiftMajor);
        sleep(0.1);
        $this->swiftOn($this->swiftTwo);
        sleep(0.5);
        $this->swiftOff($this->swiftOne);
        sleep(0.1);
        $this->swiftOff($this->swiftThree);
        self::sendMessage('автополив - клапан 2 включен');

    }

    /**
     * Включение клапана 2
     */
    public function TwoOff(): void
    {
        $this->swiftOff($this->swiftTwo);

    }

    /**
     * Включение клапана 3
     */
    public function ThreeOn(): void
    {
        $this->swiftOn($this->swiftMajor);
        sleep(0.1);
        $this->swiftOn($this->swiftThree);
        sleep(0.4);
        $this->swiftOff($this->swiftOne);
        sleep(0.2);
        $this->swiftOff($this->swiftTwo);
        self::sendMessage('автополив - клапан 3 включен');

    }

    /**
     * Включение клапана 3
     */
    public function ThreeOff(): void
    {
        $this->swiftOff($this->swiftMajor);
        sleep(0.4);
        $this->swiftOff($this->swiftThree);
        self::sendMessage('автополив - клапан 3 выключен');
    }

    /**
     * Аварийный останов всех клапанов, пользоваться в крайнем случае, для консольных команд
     */
    public function AlarmOn(): void
    {
        $this->swiftOff($this->swiftOne);
        sleep(0.1);
        $this->swiftOff($this->swiftTwo);
        sleep(0.1);
        $this->swiftOff($this->swiftThree);
        sleep(0.3); // пауза нормализует давление в системе
        $this->swiftOff($this->swiftMajor);
        self::sendMessage('автополив - команда `Авария`');

    }

    private function sendMessage($message): void
    {
        if(!empty($message)) {
            $send = new TelegramLogic();
            $send->sendByUser($message, 'decole');
        }

    }

    /**
     * Список таймеров м мх диаппазон рабочего времени
     *
     * @return array
     */
    public static function listTimers(): array
    {
        return [
            [ // start checking watering state
                'topic' => 'smart-watering/check-commands',
                'name' => 'проврка системы полива',
                'working_minutes' => 3,
                'type' => 'check',
                'id_in_db' => 2,
                'time_at' => date('H:i:s'),
            ],
            [ // start 1-th swift watering
                'topic' => 'smart-watering/one-on',
                'name' => 'включение клапана 1',
                'working_minutes' => 10,
                'type' => 'scenario',
                'id_in_db' => 4,
                'time_at' => '17:55:00',
            ],
            [ // start 2-nd swift watering
                'topic' => 'smart-watering/two-on',
                'name' => 'включение клапана 2',
                'working_minutes' => 70,
                'type' => 'scenario',
                'id_in_db' => 6,
                'time_at' => null,
            ],
            [ // start 3-th swift watering
                'topic' => 'smart-watering/three-on',
                'name' => 'включение клапана 3',
                'working_minutes' => 60,
                'type' => 'scenario',
                'id_in_db' => 8,
                'time_at' => null,
            ],
            [ // ending life cycle , turn-off all swifts
                'topic' => 'smart-watering/major-off',
                'name' => 'отключение полива',
                'working_minutes' => 1,
                'type' => 'scenario',
                'id_in_db' => 9,
                'time_at' => null,
            ],
            [ // start 1-th swift watering
                'topic' => 'smart-watering/one-on',
                'name' => 'включение клапана 1',
                'working_minutes' => 90,
                'type' => 'scenario',
                'id_in_db' => 11,
                'time_at' => '16:10:00',
            ],
            [ // ending life cycle , turn-off all swifts
                'topic' => 'smart-watering/major-off',
                'name' => 'отключение полива',
                'working_minutes' => 1,
                'type' => 'scenario',
                'id_in_db' => 12,
                'time_at' => null,
            ],
        ];

    }

}
