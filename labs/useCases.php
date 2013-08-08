<?php
// just a draft how it should or not work.
/*
use Camarera\ModelGetConfig;

use Camarera\CollectionGetConfig;

$User = User::get(1);
// update timestamp
$User->save();

$Videos = $User->VideoCollection;
$Tag = Tag::get(array('title'=>'competitor'));
$Videos->addTag($Tag);
$Videos->save();

$TaggedVideos = $Tag->VideoCollection;

$Video = Video::get()->addTag($Tag);
$TaggedVideosAlternate = VideoCollection::get()->load($Video);

$AllTagsOccuringWithCompetitor = $TaggedVideos->TagCollection;

$Author = User::get(array('status'=>User::STATUS_ACTIVE))
	->addTag('competitor')
	->addTag('amateur');
$Video = Video::get()
	->setAuthor($Author);
$VideosWithActiveUsersHavingTagsOfCompetitorAndAmateur = VideoCollection::get()->load($Video);

$LatestVideos = VideoCollection::get(
		CollectionGetConfig::get(array(
				'loadOrder'=>'_id DESC',
				'loadLimit'=>3,
		))
);

$Video1 = Video::get(ModelGetConfig::get(array('loadOrder'=>'_id DESC')));
$Video2 = Video::get(ModelGetConfig::get(array('loadOrder'=>'view_cnt DESC')));
$Video3 = Video::get(ModelGetConfig::get(array('loadOrder'=>'like_cnt DESC')));

$VideoSamples = VideoCollection::get(array($Video1, $Video2, $Video3));

$SampleUsers = $VideoSamples->UserCollection;
$SampleUsersAlternate = VideoCollection::get(array($Video1->UserID, $Video2->UserID, $Video3->UserID));

*/

$User = User::get(1);
$User->save();
User::get(1)
	->setValue('verified', true)
	->setVerifiedAt(time())
	->save();

$Videos = $User->VideoCollection->load();
$Tag = Tag::get(array('title'=>'competitor'));
$Videos->addTag($Tag);
$Videos->save();

$VideosWithTag = $Tag->VideoCollection;

$Author = User::get(array('status'=>User::STATUS_ACTIVE))
	->addTag('competitor')
	->addTag('amateur');
$Video = Video::get()
	->setAuthor($Author);
$VideosWithActiveUsersHavingTagsOfCompetitosAndAmateur = VideoCollection::get()
	->load($Video);

$LatestVideos = VideoCOllection::get(
	CollectionBuilder::typeGet()
		->setOrder('_id','DESC')
		->setLimit(3)
);

$Video1 = Video::get(
	ModelConfig::typeGet()->setOrder('_id', 'DESC')
);
$Video2 = Video::get(
	ModelConfig::typeGet()->setOrder('view_cnt DESC')
);
$Video3 = Video::get(
	ModelConfig::typeGet()->setOrder('like_cnt', 'DESC')
);

$VideoSamples = VideoCollection::get(array($Video1, $Video2, $Video3));

$SampleUsers = $VideoSamples->UserCollection;
$SampleUsersAlternate = VideoCollection::get(array($Video1->UserID, $Video2->UserID, $Video3->UserID))->UserCollection;

