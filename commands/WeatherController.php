<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace app\commands;

use app\models\Weather;
use DateTime;
use Yii;
use yii\console\Controller;
use yii\db\ActiveRecord;

/**
 * This command echoes the first argument that you have entered.
 *
 * This command is provided as an example for you to learn how to create console commands.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class WeatherController extends Controller {
	/**
	 * This command echoes what you have entered as the message.
	 *
	 * @param add weather data from AcuWeather in DB
	 */
	public function actionIndex() {

		$temp         = "Null";
		$weather_spec = "Null";

		$page    = file_get_contents( 'http://apidev.accuweather.com/currentconditions/v1/291309.json?language=ru-ru&apikey=hoArfRosT1215' );
		$decoded = json_decode( $page, true );
		if ( is_array( $decoded ) ) {
			if ( ! empty( $decoded[0]['Temperature']['Metric']['Value'] ) ) {
				$temp = $decoded[0]['Temperature']['Metric']['Value'];
			}
			$weather_spec = $decoded[0]['WeatherText']; //."|".$decoded[0]['WeatherIcon']; // ясно, пасмурно
		}

		$customer              = new Weather();
		$customer->temperature = $temp;
		$customer->spec        = $weather_spec;
		$customer->date        = date( "Y-m-d H:i:s" );
		print_r( $customer->save() );

	}

	/**
	 * @return data of date 2017-06-18
	 */
	public function actionRecuperate() {
		$status  = false;
		$weather = new Weather();
//        $weatherMassive = $weather::find()->where(['date' => '2017-06-18'])->asArray()->all();
		$weatherMassive = $weather::find()->where( [ 'like', 'date', '2017-06-18' ] )->asArray()->all();
		var_dump( $weatherMassive );
	}

    /**
     * @return stay uniqie data of weather
     */
	public function actionRedb() {
		$model = new Weather();
		$db_update = 'none';
		$dateTo    = [
			'2017-10-17', // + 180 дней обработки вперед
		];

		$date = array();
		for ( $i = 1; $i <= 180; $i ++ ) {
			$dateIs = DateTime::createFromFormat( 'Y-m-d', $dateTo[0] );
			$dateIs->modify( '+1 day' );
			$date[]    = $dateIs->format( 'Y-m-d' );
			$dateTo[0] = $dateIs->format( 'Y-m-d' );
		}

		foreach ( $date as $value ) {
			$date = DateTime::createFromFormat( 'Y-m-d', $value );
			$date->modify( '+1 day' );
			$tomorrow = $date->format( 'Y-m-d' );
			$sql      = "SELECT * FROM `weather` WHERE date > '" . $value . "' and date < '" . $tomorrow . "'";
			$result   = $model::findBySql( $sql )->asArray()->all();

			$las_val = '';
			foreach ( $result as $key => $val ) {
				if ( $las_val == $val['temperature'] ) {
					Yii::$app->db->createCommand( "DELETE FROM decole.weather WHERE `id` = $val[id]" )->execute();
				}
				$las_val = $val['temperature'];
			}
			$sql       = "SELECT * FROM `weather` WHERE date > '" . $value . "' and date < '" . $tomorrow . "'";
			$resultend = $model::findBySql( $sql )->asArray()->all();

			print_r($result);
			print_r($resultend);

			sleep(0.5);
		}

	}

    /**
     * @return print date now
     */
	public function actionGetDate()
    {
        echo Date("d m Y H:i:s");
    }

}
