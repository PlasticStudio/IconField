<ul $AttributesHTML>
	<% loop $Options %>
		<li class="option">
			<input id="$ID" class="radio" name="$Name" type="radio" value="$Value"<% if $isChecked %> checked<% end_if %> />
			<label for="$ID">
                <% if Value %>
                	<img src="$Up.getFullRelativePath($Value)" />
                <% end_if %>
            </label>
		</li>
	<% end_loop %>
</ul>
