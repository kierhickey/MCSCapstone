<p>{{summaryText}}</p>
<form>
    <label for="room-filter"/>
    <select name="room-filter">
        {{#each roomOptions}}
        <option value="{{key}}">{{display}}</option>
        {{/each}}
    </select>
    <label for="user-filter"/>
    <select name="user-filter">
        {{userOptions}}
    </select>
</form>
<div class="calendar-div">

</div>
<div class="selected-summary">

</div>
<script src="webroot/js/calendar.js" type="text/javascript"></script>
