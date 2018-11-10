<?php

namespace JPI\API\Entity;

class ProjectImage extends Entity {

	public $tableName = 'PortfolioProjectImage';

	public $displayName = 'Project Image';

	public $defaultOrderingByColumn = 'NUMBER';

	public $defaultOrderingByDirection = 'ASC';

	public $columns = [
		'ID',
		'File',
		'ProjectID',
		'Number'
	];
}