<?php
/**
 * Created by PhpStorm.
 * User: Julian Raab
 * Date: 15.04.2018
 * Time: 19:03
 */

namespace JRemmurd\IgniteBundle\Controller;


use JRemmurd\IgniteBundle\Ignite\Channel\Message;
use JRemmurd\IgniteBundle\Ignite\Event\Notification;
use JRemmurd\IgniteBundle\Ignite\Radio;
use JRemmurd\IgniteBundle\Model\Notification\Listing;
use Pimcore\Controller\FrontendController;
use Pimcore\Model\DataObject\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class ExampleController
 * @package JRemmurd\IgniteBundle\Controller
 *
 * @Route("/ignite")
 */
class ExampleController extends FrontendController
{

    /**
     * @param Request $request
     * @param Radio $radio
     * @Route("")
     * @throws \Exception
     */
    public function indexAction(Request $request, Radio $radio)
    {
        if ($user = $this->getUser()) {
            $radio
                ->getChannel("user", ["id" => $user->getId()])
                ->subscribe();
        }

        $radio
            ->getChannel("global")
            ->subscribe();

        $radio
            ->getChannel("notifications")
            ->subscribe();

        $notifications = new Listing();
        $notifications->addConditionParam("`read` IS NULL");
        $notifications->setLimit(5);
        $notifications->setOrder("desc");
        $notifications->setOrderKey("creationDate");

        $this->view->notifications = $notifications;
    }

    /**
     * @Route("/publish/public")
     *
     * @param Request $request
     * @param Radio $radio
     * @return Response
     * @throws \Exception
     */
    public function publishToPublicChannel(Request $request, Radio $radio)
    {
        $globalChannel = $radio->getPublicChannel("global");
        $child_1 = $radio->getPublicChannel("global.child_1");
        $child_2 = $radio->getPublicChannel("global.child_2");

        $globalChannel->publish(new Message("Hello from Global!"));
        $child_1->publish(new Message("Hello from Global Child 1!"));
        $child_2->publish(new Message("Hello from Global Child 2!"));

        return new Response("Published to channel with signature {$globalChannel->getSignature()}. 
        Published to channel with signature {$child_1->getSignature()}. 
        Published to channel with signature {$child_2->getSignature()}.");
    }

    /**
     * @Route("/publish/presence")
     *
     * @param Request $request
     * @param Radio $radio
     * @return Response
     * @throws \Exception
     */
    public function publishToPresenceChannel(Request $request, Radio $radio)
    {
        $userId = $this->getUser() ? $this->getUser()->getId() : 1;

        $channel = $radio
            ->getPresenceChannel("user", [
                "id" => $userId
            ])
            ->publish(new Message("Hello from User!"));

        return new Response("Published to channel with signature {$channel->getSignature()}.");
    }

    /**
     * @Route("/publish/notification")
     *
     * @param Request $request
     * @param Radio $radio
     * @return Response
     * @throws \Exception
     */
    public function publishToNotificationChannel(Request $request, Radio $radio)
    {
        $radio->setChannelNamespace("admin");
        $notifiedChannel = $radio->getPrivateChannel("user_notifications", ["id" => 2]);

        $element = User::getById(2);
        $notifiedChannel
            ->publish(new Notification("Hello Notification", "Lorem ipsum dolor sit amet!"))
            ->publish(new Notification(
                    "Hello Notification with data",
                    "Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore.",
                    $element)
            );

        return new Response("Published to channel with signature {$notifiedChannel->getSignature()}.");
    }

}