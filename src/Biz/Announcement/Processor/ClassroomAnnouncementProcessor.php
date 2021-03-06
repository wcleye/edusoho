<?php

namespace Biz\Announcement\Processor;

use Biz\Classroom\Service\ClassroomService;
use Biz\CloudPlatform\QueueJob\PushJob;
use Biz\System\Service\SettingService;
use Codeages\Biz\Framework\Queue\Service\QueueService;
use Topxia\Service\Common\ServiceKernel;
use Biz\User\Service\NotificationService;

class ClassroomAnnouncementProcessor extends AnnouncementProcessor
{
    public function checkManage($targetId)
    {
        return $this->getClassroomService()->canManageClassroom($targetId);
    }

    public function checkTake($targetId)
    {
        return $this->getClassroomService()->canTakeClassroom($targetId);
    }

    public function getTargetShowUrl()
    {
        return 'classroom_show';
    }

    public function announcementNotification($targetId, $targetObject, $targetObjectShowUrl)
    {
        $count = $this->getClassroomService()->searchMemberCount(array('classroomId' => $targetId, 'role' => 'student'));

        $members = $this->getClassroomService()->searchMembers(
            array('classroomId' => $targetId, 'role' => 'student'),
            array('createdTime' => 'DESC'),
            0, $count
        );

        $result = false;
        if ($members) {
            $message = array('title' => $targetObject['title'],
                'url' => $targetObjectShowUrl,
                'type' => 'classroom', );
            foreach ($members as $member) {
                //@todo 等移动端开放之后再放开
                //                $this->classroomAnnouncementPush($member);
                $result = $this->getNotificationService()->notify($member['userId'], 'learn-notice', $message);
            }
        }

        return $result;
    }

    private function classroomAnnouncementPush($member)
    {
        if (!$this->isIMEnabled()) {
            return;
        }

        $classroom = $this->getClassroomService()->getClassroom($member['classroomId']);

        $from = array(
            'id' => $classroom['id'],
            'type' => 'course',
        );

        $to = array(
            'type' => 'classroom',
            'id' => 'all',
            'convNo' => $this->getConvNo(),
        );

        $body = array(
            'type' => 'course_announcement.create',
            'classroomId' => $classroom['id'],
            'title' => "[班级公告] 你正在学习的班级《{$classroom['title']}》有一个新的公告，快去看看吧",
        );

        $this->createPushJob($from, $to, $body);
    }

    private function createPushJob($from, $to, $body)
    {
        $pushJob = new PushJob(array(
            'from' => $from,
            'to' => $to,
            'body' => $body,
        ));

        $this->getQueueService()->pushJob($pushJob);
    }

    private function getConvNo()
    {
        $imSetting = $this->getSettingService()->get('app_im', array());
        $convNo = isset($imSetting['convNo']) && !empty($imSetting['convNo']) ? $imSetting['convNo'] : '';

        return $convNo;
    }

    public function isIMEnabled()
    {
        $setting = $this->getSettingService()->get('app_im', array());

        if (empty($setting) || empty($setting['enabled'])) {
            return false;
        }

        return true;
    }

    public function tryManageObject($targetId)
    {
        $this->getClassroomService()->tryManageClassroom($targetId);
        $classroom = $this->getClassroomService()->getClassroom($targetId);

        return $classroom;
    }

    public function getTargetObject($targetId)
    {
        return $this->getClassroomService()->getClassroom($targetId);
    }

    public function getActions($action)
    {
        $config = array(
            'create' => 'AppBundle:Classroom/Announcement:create',
            'edit' => 'AppBundle:Classroom/Announcement:edit',
            'list' => 'AppBundle:Classroom/Announcement:list',
        );

        return $config[$action];
    }

    /**
     * @return ClassroomService
     */
    protected function getClassroomService()
    {
        return ServiceKernel::instance()->createService('Classroom:ClassroomService');
    }

    /**
     * @return NotificationService
     */
    protected function getNotificationService()
    {
        return $this->biz->service('User:NotificationService');
    }

    /**
     * @return QueueService
     */
    protected function getQueueService()
    {
        return ServiceKernel::instance()->createService('Queue:QueueService');
    }

    /**
     * @return SettingService
     */
    protected function getSettingService()
    {
        return ServiceKernel::instance()->createService('System:SettingService');
    }
}
