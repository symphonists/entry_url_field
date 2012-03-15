<?php
	
	class Extension_Entry_URL_Field extends Extension {
		
		protected static $fields = array();
		
		public function uninstall() {
			Symphony::Database()->query("DROP TABLE `tbl_fields_entry_url`");
		}
		
		public function install() {
			Symphony::Database()->query("
				CREATE TABLE IF NOT EXISTS `tbl_fields_entry_url` (
					`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
					`field_id` INT(11) UNSIGNED NOT NULL,
					`anchor_label` VARCHAR(255) DEFAULT NULL,
					`expression` VARCHAR(255) DEFAULT NULL,					
					`new_window` ENUM('yes', 'no') DEFAULT 'no',
					`hide` ENUM('yes', 'no') DEFAULT 'no',
					PRIMARY KEY (`id`),
					KEY `field_id` (`field_id`)
				)
			");
			
			return true;
		}
		
		public function getSubscribedDelegates() {
			return array(
				array(
					'page'		=> '/publish/new/',
					'delegate'	=> 'EntryPostCreate',
					'callback'	=> 'compileBackendFields'
				),
				array(
					'page'		=> '/publish/edit/',
					'delegate'	=> 'EntryPostEdit',
					'callback'	=> 'compileBackendFields'
				),
				array(
					'page'		=> '/frontend/',
					'delegate'	=> 'EventPostSaveFilter',
					'callback'	=> 'compileFrontendFields'
				)
			);
		}
		
	/*-------------------------------------------------------------------------
		Utilities:
	-------------------------------------------------------------------------*/
		
		public function getXPath($entry) {
			$entry_xml = new XMLElement('entry');
			$section_id = $entry->get('section_id');
			$data = $entry->getData(); $fields = array();
			
			$entry_xml->setAttribute('id', $entry->get('id'));
			
			$associated = $entry->fetchAllAssociatedEntryCounts();
			
			if (is_array($associated) and !empty($associated)) {
				foreach ($associated as $section => $count) {
					$related_section = SectionManager::fetch($section);
					$entry_xml->setAttribute($related_section->get('handle'), (string)$count);
				}
			}
			
			// Add fields:
			foreach ($data as $field_id => $values) {
				if (empty($field_id)) continue;
				
				$field = FieldManager::fetch($field_id);
				$field->appendFormattedElement($entry_xml, $values, false);
			}
			
			$xml = new XMLElement('data');
			$xml->appendChild($entry_xml);
			
			$dom = new DOMDocument();
			$dom->loadXML($xml->generate(true));
			
			return new DOMXPath($dom);
		}
		
	/*-------------------------------------------------------------------------
		Fields:
	-------------------------------------------------------------------------*/
		
		public function registerField($field) {
			self::$fields[] = $field;
		}
		
		public function compileBackendFields($context) {
			foreach (self::$fields as $field) {
				$field->compile($context['entry']);
			}
		}
		
		public function compileFrontendFields($context) {
			foreach (self::$fields as $field) {
				$field->compile($context['entry']);
			}
		}
	}