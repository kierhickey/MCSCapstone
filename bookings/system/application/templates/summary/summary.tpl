<p>{{summaryText}}</p>
<form>
    <label for="room-filter">Rooms: </label>
    <select name="room-filter">
        {{#each roomOptions}}
        <option value="{{key}}">{{display}}</option>
        {{/each}}
    </select>
    <label for="user-filter">Users: </label>
    <select name="user-filter">
        {{#each userOptions}}
        <option value="{{key}}">{{display}}</option>
        {{/each}}
    </select>
</form>
<div class="calendar-div">

</div>
<div class="selected-summary">

</div>
<script src="webroot/js/calendar.js" type="text/javascript"></script>
