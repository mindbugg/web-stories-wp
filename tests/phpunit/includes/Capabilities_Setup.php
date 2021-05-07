<?php
/**
 * Copyright 2021 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     https://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Google\Web_Stories\Tests;

use Google\Web_Stories\User\Capabilities;

trait Capabilities_Setup {
	protected function get_capability_object() {
		return new Capabilities();
	}

	public function add_caps_to_roles() {
		$capability = $this->get_capability_object();
		$capability->add_caps_to_roles();
	}

	public function remove_caps_from_roles() {
		$capability = $this->get_capability_object();
		$capability->remove_caps_from_roles();
	}
}
