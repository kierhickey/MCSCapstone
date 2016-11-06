<?php echo $this->session->flashdata('auth') ?>

<div class="info" style="margin-bottom: 12.67px">
	<p>
		If you do not have an account with Melbourne Consulting Suites, contact
		our Administrator for more information;
	</p>
	<span>Email: </span><a href="mailto:&#112;&#114;&#111;&#102;&#105;&#108;&#101;&#112;&#115;&#121;&#099;&#104;&#064;&#121;&#097;&#104;&#111;&#111;&#046;&#099;&#111;&#109;">&#112;&#114;&#111;&#102;&#105;&#108;&#101;&#112;&#115;&#121;&#099;&#104;&#064;&#121;&#097;&#104;&#111;&#111;&#046;&#099;&#111;&#109;</a><br/>
	<span>Phone: </span><a href="tel:0419 368 746">0419 368 746</a>
</div>
<div class="login-panel">
	<div class="login-panel-inner">
		<?php
		$t = 1;
		echo form_open('login/submit', array('id'=>'login'), array('page' => $this->uri->uri_string()) );
		?>
			<span class="login-field-wrap">
			  <label for="username" class="login-label">Username</label><!--
			  --><?php
				$username = @field($this->validation->username);
				echo form_input(array(
					'name' => 'username',
					'id' => 'username',
					'class' => 'login-field',
					'placeholder' => 'Username',
					'size' => '20',
					'maxlength' => '20',
					'tabindex' => $t,
					'value' => $username,
					"required" => "required"
				));
				$t++;
				?>
			</span>
			<?php echo @field($this->validation->username_error); ?>


			<span class="login-field-wrap">
			  <label for="password" class="login-label">Password</label><!--
			  --><?php
				$password = @field($this->validation->password);
				echo form_password(array(
					'name' => 'password',
					'id' => 'password',
					'class' => 'login-field',
					'placeholder' => 'Password',
					'size' => '20',
					'tabindex' => $t,
					'maxlength' => '20',
					"required" => "required"
				));
				$t++;
				?>
			</span>
			<?php echo @field($this->validation->password_error); ?>
			<?php echo "<br/><span class='login-button-panel'><input type='submit' class='login-submit login-button' value='Login'/><input class='login-reset login-button' type='reset' /></span>"; ?>
		<?php
		echo form_close();
		?>
	</div>
</div>
