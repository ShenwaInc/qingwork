<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateMcMemberAddressTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('mc_member_address', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('uniacid')->unsigned()->index('idx_uinacid');
			$table->integer('uid')->unsigned()->index('idx_uid');
			$table->string('username', 20);
			$table->string('mobile', 11);
			$table->string('zipcode', 6);
			$table->string('province', 32);
			$table->string('city', 32);
			$table->string('district', 32);
			$table->string('address', 512);
			$table->boolean('isdefault');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('mc_member_address');
	}

}