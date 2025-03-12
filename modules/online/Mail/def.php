<?php
namespace Mail;

class def {
	public static $servers = [
		"gmail.com" => [
			// smtp - 465 или 587
			"login"=>"full",
			"indomain"=> "pop.gmail.com", "inport"=> 995,	"poptls"=>1,	"popauth"=>1,
			"outdomain"=> "smtp.gmail.com", "outport"=> 587, "smtptls"=>1, "smtpauth"=>1
		],
		//mail.ru, bk.ru, list.ru, inbox.ru - ящики mail.ru
		"mail.ru"=> [
			"login"=> "small",
			"indomain"=> "pop.mail.ru", "inport"=> 995,	"poptls"=>1,	"popauth"=>0,
			"outdomain"=> "smtp.mail.ru", "outport"=> 25, "smtptls"=>1, "smtpauth"=>1
		],
		"list.ru"=> [
			"login"=> "small",
			"indomain"=> "pop.list.ru", "inport"=> 995,	"poptls"=>1,	"popauth"=>0,
			"outdomain"=> "smtp.list.ru", "outport"=> 25, "smtptls"=>0, "smtpauth"=>1
		],
		"bk.ru"=> [
			"login"=> "small",
			"indomain"=> "pop.bk.ru", "inport"=> 995,	"poptls"=>1,	"popauth"=>0,
			"outdomain"=> "smtp.bk.ru", "outport"=> 25, "smtptls"=>0, "smtpauth"=>1
		],
		"inbox.ru"=> [
			"login"=> "small",
			"indomain"=> "pop.inbox.ru", "inport"=> 995,	"poptls"=>1,	"popauth"=>0,
			"outdomain"=> "smtp.inbox.ru", "outport"=> 25, "smtptls"=>0, "smtpauth"=>1
		],
		"ukr.net"=> [
			// smtp проверить дома ()
			"login"=> "small",
			"indomain"=> "pop3.ukr.net", "inport"=> 110,	"poptls"=>0,	"popauth"=>0,
			"outdomain"=> "smtp.ukr.net", "outport"=> 465, "smtptls"=>1, "smtpauth"=>1
		],
		"rambler.ru"=> [
			"login"=> "small",
			"indomain"=> "mail.rambler.ru", "inport"=> 110,	"poptls"=>0,	"popauth"=>0,
			"outdomain"=> "smtp.rambler.ru", "outport"=> 587, "smtptls"=>0, "smtpauth"=>1
		],
		"mgg.ua"=> [
			"login"=> "full",
			"indomain"=> "router.local", "inport"=> 110,	"poptls"=>0,	"popauth"=>1,
			"outdomain"=> "router.local", "outport"=> 25, "smtptls"=>0, "smtpauth"=>0
		],
		"trud.net"=> [
			"login"=> "full",
			"indomain"=> "router.local", "inport"=> 110,	"poptls"=>0,	"popauth"=>1,
			"outdomain"=> "router.local", "outport"=> 25, "smtptls"=>0, "smtpauth"=>0
		],
		"default"=> [
			// к pop и smtp добавить имя сервера
			"login"=> "small",
			"indomain"=> "pop.", "inport"=> 110,	"poptls"=>0,	"popauth"=>0,
			"outdomain"=> "smtp.", "outport"=> 25, "smtptls"=>0, "smtpauth"=>0
		]
	];
}