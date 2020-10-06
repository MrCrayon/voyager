<input @if($row->required == 1) required @endif @if(!empty($options->readonly)) readonly @endif type="text" class="form-control" name="{{ $row->field }}"
        placeholder="{{ old($row->field, $options->placeholder ?? $row->getTranslatedAttribute('display_name')) }}"
       {!! isBreadSlugAutoGenerator($options) !!}
       @if(isset($options->pattern)) pattern="{{ $options->pattern }}" @endif
       value="{{ old($row->field, $dataTypeContent->{$row->field} ?? $options->default ?? '') }}">
