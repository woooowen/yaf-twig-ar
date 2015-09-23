<?php
class ViewJson implements Yaf_View_Interface
{

	protected $variables = array();

	public function assign($name, $value = null)
	{
		$this->variables[$name] = $value;
	}

	public function getScriptPath()
	{
		return '';
	}

	public function setScriptPath($templateDir)
	{
	}

	public function display($tpl, $variables = null)
	{
		echo $this->render($tpl, $variables);
	}

	public function render($tpl, $variables = null)
	{
		if (is_array($variables)) {
			$this->variables = array_merge($this->variables, $variables);
		}
		return json_encode($this->variables);
	}

}
