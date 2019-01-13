<?php
namespace app\commands;

use Yii;
use yii\console\Controller;

class RbacController extends Controller
{
    public function actionInit()
    {
        $auth = Yii::$app->authManager;
        
        $changeArduino = $auth->createPermission('changeArduino');
        $changeArduino->description = 'change Arduino controllers and sensors';
        $auth->add($changeArduino);

        $allAcces = $auth->createPermission('allAcces');
        $allAcces->description = 'all acces';
        $auth->add($allAcces);

        $user = $auth->createRole('user');
        $auth->add($user);
        $auth->addChild($user, $changeArduino);

        $admin = $auth->createRole('admin');
        $auth->add($admin);
        $auth->addChild($admin, $allAcces);
        $auth->addChild($admin, $user);

        $auth->assign($user, 2);
        $auth->assign($admin, 1);
    }
}
