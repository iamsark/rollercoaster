<link type="text/css" rel="stylesheet" href="dark-knight/assets/styles/style.css">	

<div class="rc-master-container">

	<!-- Top menu section starts -->				
	<div class="rc-grid-row">
	
		<div id="rc-left-nav-col" class="rc-grid-col-3">
		
			<div class="rc-branding-container">
			
				<?php RC()->hook->trigger_action( "rc_logo" ); ?>
				
			</div>
			
			<div class="rc-compose-btn-container">
				<button id="rc-compose-btn" class="rc-btn rc-btn-primary rc-btn-compose">Compose</button>
			</div>
		
			<div class="rc-left-nav-container">			
				
				<?php RC()->hook->trigger_action( "rc_left_nav_section" ); ?>
				
				<div id="rc-mail-mode-toggle-container">
					<button class="rc-mail-mode-toggle-btn disabled" data-mode="viewer" title="Switch to Viewer Mode">Viewer</button>
					<button class="rc-mail-mode-toggle-btn disabled" data-mode="composer" title="Switch to Composer Mode">Composer</button>
				</div>
							
			</div>
		
		</div>
		
		<!-- Email list section starts here -->
			
		<div id="rc-mail-list-section" class="rc-grid-col-6">
		
			<table class="rc-account-info-table">			
				<tr>
					<td>
						<span class="rc-account-user-letter">S</span>	
					</td>
					<td>
						<label class="rc-account-user-email"><?php $user = RC()->context->get_user(); echo $user->get_email(); ?></label>
						<ul class="rc-account-menu-ul">
							<li>
								<a href="#" id="rc-account-user-bulk-menu" title="Perform bulk mail actions"><i class="fa fa-th-list"></i> Bulk Action</a>
								<div id="rc-mail-bulk-action-drop">
								<?php								
									/* Load bulk action list */
									RC()->template->load_bulk_actions(); ?>
								</div>
							</li>
							<li><a href="#" id="rc-account-user-pref-menu" title="User preferences"><i class="fa fa-gear"></i> Preference</a></li>
							<li><a href="#" id="rc-account-user-logout-menu" title="Logout this session"><i class="fa fa-sign-out"></i> Logout</a></li>
						</ul>						
					</td>
				</tr>
			</table>
		
			<div class="rc-mail-list-search-container">
				<input type="text" id="rc-mail-search-text" placeholder="Search Here ..." />
				<a href="#" id="rc-mail-search-type-btn" title="Search From">
					<i class="fa fa-caret-down"></i>
					<div id="rc-mail-search-type-dropdown" class="rc-mail-search-drop-container rc-mail-drop-container">
						<?php
							/* Load search type */
							RC()->template->load_search_types();
						?>
					</div>
				</a>
			</div>
			
			<div class="rc-mail-list-filter-container">
				<div class="rc-grid-row">
					<div class="rc-grid-col-12">
						<label class="rc-mail-list-check-all"><input type="checkbox" id="rc-mail-select-all-check" title="Select All Mail Headers"></label>
						<a href="#" class="rc-folder-filter-btn selected" data-filter="all">All</a>
						<a href="#" class="rc-folder-filter-btn" data-filter="unread">Unread</a>
					</div>
					<div class="rc-grid-col-12 rc-sorting-btn-container">
						<a href="#" class="rc-folder-sort-btn" data-sort="ASC"><i class="fa fa-long-arrow-up"></i> Oldest</a>
						<a href="#" class="rc-folder-sort-btn selected" data-sort="DSC"><i class="fa fa-long-arrow-down"></i> Newest</a>
					</div>
				</div>
			</div>
			
			<div id="rc-mail-header-container">
			<!-- This is where the actual mail headers will be listed out -->		
			<table class="rc-empty-folder-info"><tr><td><h3><i class="fa fa-info-circle"></i> Please select any folder.!</h3></td></tr></table>			
			</div> 
		
		</div>
		
		<!-- Email list section ends here -->
				
		<div id="rc-mail-viewer-section" class="rc-grid-col-15">
		
			<!-- Email viewer section starts here -->
		
			<div id="rc-mail-viewer-view-container" data-view="viewer" style="display: none;">
				<div class="rc-tab rc-mail-viewer-tab-header" data-tab="rc-tab-viewer-content-container">
					
				</div>
				<div class="rc-tab-content rc-mail-viewer-tab-content" id="rc-tab-viewer-content-container">
					
				</div>
			</div>
			
			<!-- Email viewer section ends here -->
			
			<!-- Composer section starts here -->
			
			<div id="rc-mail-viewer-compose-container" data-view="compose" style="display: none;">
			
				<!-- Composer UI will be injected here -->
				<div class="rc-tab rc-mail-composer-tab-header" data-tab="rc-tab-composer-content-container">
					
				</div>
				<div class="rc-tab-content rc-mail-composer-tab-content" id="rc-tab-composer-content-container">
					
				</div>
			
			</div>
			
			<!-- Composer section ends here -->
			
			<?php RC()->hook->trigger_action( "rc_welcome_section" ); ?>
						
		</div>
	
	</div>
	
</div>		
