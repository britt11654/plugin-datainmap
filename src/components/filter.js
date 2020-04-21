/*
* Copyright 2020 Gemeente Heerenveen
*
* Licensed under the EUPL, Version 1.2 or – as soon they will be approved by the European Commission - subsequent versions of the EUPL (the "Licence");
* You may not use this work except in compliance with the Licence.
* You may obtain a copy of the Licence at:
*
* https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
*
* Unless required by applicable law or agreed to in writing, software distributed under the Licence is distributed on an "AS IS" basis,
* WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
* See the Licence for the specific language governing permissions and limitations under the Licence.
*/
import React, {Component} from 'react';
import _ from 'lodash';

class FilterItemComponent extends Component {
    render() {
        return (
            <>
            <input type="checkbox" onChange={(e) => this.props.handleChange(this.props.term, e.currentTarget.checked)} /> {this.props.term.name}<br/>
            </>
        )
    }
}

export class FilterComponent extends Component {
    constructor(props) {
        super(props);
    }

    handleFilterChange(term, checked) {
        // console.log(term, checked);
        this.props.doSetFilter(term.term_id, checked);
    }

    render() {
        return (
            <div className="gh-dim-filter">
                {this.props.filters.map((term) => {
                    return <FilterItemComponent key={term.term_id} term={term} handleChange={(term, checked) => this.handleFilterChange(term, checked)} />
                })}
            </div>
        )
    }
}

FilterComponent.defaultProps = {
    doSetFilter: _.noop,
    filters: [],
    selected: []
};

export default FilterComponent;