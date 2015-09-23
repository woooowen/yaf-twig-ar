<?php
class ViewJson implements Yaf_View_Interface
{

	protected $variables = array();

	public function assign($name, $value = null)
	{
		$this->variables[$name] = $value;
	}

	public function display($template, $variables = null)
	{
		echo $this->render($template, $variables);
	}

	public function render($template, $variables = null)
	{
		if (is_array($variables)) {
			$this->variables = array_merge($this->variables, $variables);
		}
		return json_encode($this->variables);
	}
}
