该模块的设计目的是解决抽奖逻辑中可能遇到的问题
1 抽奖凭证 lottery_identity表的说明

有些项目 可能需要用到微博UID来作为抽奖凭证，而有些项目需要用到微信ID作为抽奖凭证，
一般的做法是在相应的表中增加这些字段（weibUid，FromUserName）
比如说exchange表中目前就有这2个字段，这样一来如果项目需求发生改变的时候，
表结构必然要发生改变。所以将这一部分抽离出来，单独设计了这个表
这样如果需求发生改变的时候，只要在这个表中增加所需的字段就可以了。其他的表结构不用改变
目前该表有以下的字段
姓名 是用来应对 当某用户只有填写了姓名之后，才能抽奖的需求
手机号 是用来应对 当某用户只有填写了手机号之后，才能抽奖的需求
微博UID 是用来应对 当某用户只有填写了微博UID之后，才能抽奖的需求
微信ID 是用来应对 当某用户只有填写了微信ID之后，才能抽奖的需求

Lottery_Model_LotteryIdentity里面有以下程序可能需要须改
		//以下条件可以根据具体业务来决定
    	//比如说如果抽奖是按照微信号来决定唯一性的话，那么就将最后一个IF语句提到最前
    	if(!empty($mobile)){
    		$query['mobile'] =$mobile;
    		$info = $this->findOne($query);
    	}else if(!empty($name)){
    		$query['name'] =$name;
    		$info = $this->findOne($query);
    	}else if(!empty($weibo_uid)){
    		$query['weibo_uid'] =$weibo_uid;
    		$info = $this->findOne($query);
    	}else if(!empty($FromUserName)){
    		$query['FromUserName'] =$FromUserName;
    		$info = $this->findOne($query);
    	}
    	
另外 该表可以增加另外的字段，来完成特殊的业务逻辑，比如说IP

2 奖品表prize的说明
在这个表中 增加了 是否是大奖，是否是实物奖，虚拟币，奖品图片等字段。
是否是大奖 是用来应对 当某用户中过了大奖之后，就不能再次中大奖的需求
是否是实物奖 是用来应对 如果某用户中了是实物奖时候，可能需要填写用户信息，而中了虚拟奖的时候，不要填写用户信息的需求
虚拟币 是用来应对 某些奖品是虚拟奖品，他的价值是什么。比如说10个积分，5个Q币等奖品的时候，可以设置这个字段
奖品图片 是用来应对 客户端可能需要显示奖品的图片的需求

另外 奖品表可以增加另外的字段，来完成特殊的业务逻辑
 
3 中奖限制number_limit表的说明
目前该表中已经添加了2个数据，说明如下
一天参与次数条件today_lottery 将它设置为0的时候，没有这个限制，如果设置成某个具体的数值的时候，该限制就生效
解决 玩家在一天内只能参与抽奖几次的问题

中奖次数条件prize_limit 将它设置为0的时候，没有这个限制，如果设置成某个具体的数值的时候，该限制就生效
本来的目的是解决 玩家一旦中过奖之后不能再次中奖的问题，而我把它更加引申了一下，解决玩家一旦中过n次奖之后不能再次中奖的问题

这个表可以增加另外的数据，来完成特殊的业务逻辑