<?php
/**
 * Python Bridge for OpenAI Agents SDK
 *
 * @package MemberPress AI Assistant
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Bridge between PHP and Python for SDK integration
 */
class MPAI_Py_Bridge {
	/**
	 * Python executable path
	 * 
	 * @var string
	 */
	private $python_path;
	
	/**
	 * SDK scripts directory
	 * 
	 * @var string
	 */
	private $scripts_dir;
	
	/**
	 * Logger instance
	 * 
	 * @var object
	 */
	private $logger;
	
	/**
	 * Constructor
	 * 
	 * @param object $logger Logger.
	 */
	public function __construct( $logger ) {
		$this->logger = $logger;
		$this->python_path = $this->detect_python_path();
		$this->scripts_dir = plugin_dir_path( dirname( dirname( __FILE__ ) ) ) . '../sdk/';
	}
	
	/**
	 * Detect Python executable path
	 * 
	 * @return string Python path.
	 */
	private function detect_python_path() {
		// First check settings
		$custom_path = get_option( 'mpai_python_path', '' );
		
		if ( ! empty( $custom_path ) && is_executable( $custom_path ) ) {
			return $custom_path;
		}
		
		// Try to detect Python 3.8+ automatically
		$potential_paths = [ 'python3', 'python' ];
		
		foreach ( $potential_paths as $path ) {
			$output = [];
			$return_var = -1;
			exec( $path . ' --version 2>&1', $output, $return_var );
			
			if ( $return_var === 0 && ! empty( $output ) ) {
				$version_string = implode( ' ', $output );
				if ( preg_match( '/Python\s+3\.([8-9]|1[0-9])/', $version_string ) ) {
					return $path;
				}
			}
		}
		
		// Default to python3
		return 'python3';
	}
	
	/**
	 * Verify Python environment
	 * 
	 * @return bool Whether environment is valid.
	 */
	public function verify_environment() {
		try {
			$result = $this->execute_command( $this->python_path . ' --version' );
			
			if ( empty( $result['success'] ) ) {
				$this->logger->log( 'error', 'Python version check failed: ' . $result['output'] );
				return false;
			}
			
			// Check Python version (need 3.8+)
			if ( ! preg_match( '/Python\s+3\.([8-9]|1[0-9])/', $result['output'] ) ) {
				$this->logger->log( 
					'error', 
					'Python version incompatible. Need 3.8+, found: ' . $result['output']
				);
				return false;
			}
			
			return true;
		} catch ( Exception $e ) {
			$this->logger->log( 'error', 'Python environment verification failed: ' . $e->getMessage() );
			return false;
		}
	}
	
	/**
	 * Verify SDK installation
	 * 
	 * @return bool Whether SDK is properly installed.
	 */
	public function verify_sdk_installation() {
		try {
			// First, create the check_sdk.py file if it doesn't exist
			$this->ensure_check_sdk_script_exists();
			
			$check_script = $this->scripts_dir . 'check_sdk.py';
			
			if ( ! file_exists( $check_script ) ) {
				$this->logger->log( 'error', 'SDK check script not found at: ' . $check_script );
				return false;
			}
			
			$result = $this->execute_command( $this->python_path . ' ' . escapeshellarg( $check_script ) );
			
			if ( empty( $result['success'] ) ) {
				$this->logger->log( 'error', 'SDK check failed: ' . $result['output'] );
				return false;
			}
			
			$sdk_status = json_decode( $result['output'], true );
			
			if ( ! $sdk_status || empty( $sdk_status['installed'] ) ) {
				$this->logger->log( 
					'error', 
					'SDK not installed or invalid: ' . 
						( is_array( $sdk_status ) ? json_encode( $sdk_status ) : 'Invalid response' )
				);
				return false;
			}
			
			return true;
		} catch ( Exception $e ) {
			$this->logger->log( 'error', 'SDK installation verification failed: ' . $e->getMessage() );
			return false;
		}
	}
	
	/**
	 * Ensure check_sdk.py script exists
	 */
	private function ensure_check_sdk_script_exists() {
		$check_script_path = $this->scripts_dir . 'check_sdk.py';
		
		if ( ! file_exists( $check_script_path ) ) {
			$check_script_content = <<<'PYTHON'
#!/usr/bin/env python3
import sys
import json
import os
import importlib.util

def check_sdk_installation():
    """Check if the OpenAI Agents SDK is installed and compatible"""
    try:
        # Check for openai package
        openai_spec = importlib.util.find_spec("openai")
        if not openai_spec:
            return {
                "installed": False,
                "error": "OpenAI package not installed"
            }
            
        # Try to import the agents module
        try:
            import openai.agents
            
            # Check version
            version = getattr(openai.agents, "__version__", "unknown")
            
            return {
                "installed": True,
                "version": version
            }
        except ImportError:
            return {
                "installed": False,
                "error": "OpenAI agents module not available"
            }
    except Exception as e:
        return {
            "installed": False,
            "error": str(e)
        }

if __name__ == "__main__":
    result = check_sdk_installation()
    print(json.dumps(result))
PYTHON;

			file_put_contents( $check_script_path, $check_script_content );
			chmod( $check_script_path, 0755 );
		}
	}
	
	/**
	 * Execute a Python script with parameters
	 * 
	 * @param string $script_name Script name in SDK directory.
	 * @param array  $params Parameters to pass to script.
	 * @return array Execution result.
	 */
	public function execute_script( $script_name, $params = [] ) {
		$script_path = $this->scripts_dir . $script_name;
		
		// Create the script if it doesn't exist
		$this->ensure_script_exists( $script_name );
		
		if ( ! file_exists( $script_path ) ) {
			return [
				'success' => false,
				'error' => 'Script not found: ' . $script_name,
			];
		}
		
		// Convert params to JSON
		$params_json = wp_json_encode( $params );
		
		// Write params to temp file to avoid command line length issues
		$tmp_dir = $this->scripts_dir . 'tmp/';
		if ( ! is_dir( $tmp_dir ) ) {
			mkdir( $tmp_dir, 0755, true );
		}
		
		$params_file = $tmp_dir . 'params_' . uniqid() . '.json';
		file_put_contents( $params_file, $params_json );
		
		// Build command
		$command = sprintf(
			'%s %s %s',
			escapeshellarg( $this->python_path ),
			escapeshellarg( $script_path ),
			escapeshellarg( $params_file )
		);
		
		// Execute
		$result = $this->execute_command( $command );
		
		// Clean up params file
		@unlink( $params_file );
		
		if ( empty( $result['success'] ) ) {
			return $result;
		}
		
		// Parse output as JSON
		$data = json_decode( $result['output'], true );
		
		if ( json_last_error() !== JSON_ERROR_NONE ) {
			return [
				'success' => false,
				'error' => 'Failed to parse script output as JSON',
				'output' => $result['output'],
			];
		}
		
		return [
			'success' => true,
			'data' => $data,
		];
	}
	
	/**
	 * Execute a shell command
	 * 
	 * @param string $command Command to execute.
	 * @return array Execution result.
	 */
	private function execute_command( $command ) {
		$this->logger->log( 'info', 'Executing command: ' . $command );
		
		$output = [];
		$return_var = 0;
		
		exec( $command . ' 2>&1', $output, $return_var );
		
		$output_string = implode( "\n", $output );
		
		if ( $return_var !== 0 ) {
			$this->logger->log( 
				'error', 
				'Command execution failed with code ' . $return_var . ': ' . $output_string
			);
			
			return [
				'success' => false,
				'error' => 'Command execution failed with code ' . $return_var,
				'output' => $output_string,
			];
		}
		
		return [
			'success' => true,
			'output' => $output_string,
		];
	}
	
	/**
	 * Ensure script exists
	 * 
	 * @param string $script_name Script name.
	 */
	private function ensure_script_exists( $script_name ) {
		$script_path = $this->scripts_dir . $script_name;
		
		if ( file_exists( $script_path ) ) {
			return;
		}
		
		switch ( $script_name ) {
			case 'initialize_sdk.py':
				$this->create_initialize_sdk_script();
				break;
			case 'register_agent.py':
				$this->create_register_agent_script();
				break;
			case 'process_request.py':
				$this->create_process_request_script();
				break;
			case 'handoff.py':
				$this->create_handoff_script();
				break;
			case 'run_agent.py':
				$this->create_run_agent_script();
				break;
		}
	}
	
	/**
	 * Create initialize_sdk.py script
	 */
	private function create_initialize_sdk_script() {
		$script_path = $this->scripts_dir . 'initialize_sdk.py';
		$script_content = <<<'PYTHON'
#!/usr/bin/env python3
import sys
import json
import os
from pathlib import Path

try:
    from openai import OpenAI
    import openai.agents as agents
except ImportError:
    print(json.dumps({
        "success": False,
        "error": "Required packages not installed. Run: pip install -r requirements.txt"
    }))
    sys.exit(1)

def initialize_sdk():
    """Initialize the OpenAI Agents SDK configuration"""
    try:
        # Load parameters if provided
        params = {}
        if len(sys.argv) > 1 and os.path.exists(sys.argv[1]):
            with open(sys.argv[1], 'r') as f:
                params = json.load(f)
        
        # Get SDK base directory
        base_dir = Path(__file__).parent
        
        # Ensure config directory exists
        config_dir = base_dir / "config"
        config_dir.mkdir(exist_ok=True)
        
        # Create tool definitions directory
        tool_def_dir = config_dir / "tool_definitions"
        tool_def_dir.mkdir(exist_ok=True)
        
        # Create agent definitions directory
        agent_def_dir = config_dir / "agent_definitions"
        agent_def_dir.mkdir(exist_ok=True)
        
        # Create main configuration
        api_key = params.get("api_key") or os.environ.get("OPENAI_API_KEY")
        
        if not api_key:
            return {
                "success": False,
                "error": "No OpenAI API key provided"
            }
            
        # Create base configuration
        config = {
            "api_key": api_key,
            "model": params.get("model", "gpt-4o"),
            "tools_enabled": True,
            "agents": [],
            "tools": []
        }
        
        # Write config
        with open(config_dir / "config.json", 'w') as f:
            json.dump(config, f, indent=2)
            
        return {
            "success": True,
            "config_path": str(config_dir / "config.json")
        }
        
    except Exception as e:
        import traceback
        return {
            "success": False,
            "error": str(e),
            "traceback": traceback.format_exc()
        }

if __name__ == "__main__":
    result = initialize_sdk()
    print(json.dumps(result))
PYTHON;

		file_put_contents( $script_path, $script_content );
		chmod( $script_path, 0755 );
	}
	
	/**
	 * Create register_agent.py script
	 */
	private function create_register_agent_script() {
		$script_path = $this->scripts_dir . 'register_agent.py';
		$script_content = <<<'PYTHON'
#!/usr/bin/env python3
import sys
import json
import os
from pathlib import Path

try:
    from openai import OpenAI
    import openai.agents as agents
except ImportError:
    print(json.dumps({
        "success": False,
        "error": "Required packages not installed. Run: pip install -r requirements.txt"
    }))
    sys.exit(1)

def register_agent():
    """Register an agent with the SDK"""
    try:
        # Load parameters
        if len(sys.argv) <= 1 or not os.path.exists(sys.argv[1]):
            return {
                "success": False,
                "error": "No parameters file provided"
            }
            
        with open(sys.argv[1], 'r') as f:
            params = json.load(f)
            
        if not params.get("agent_id") or not params.get("agent_config"):
            return {
                "success": False,
                "error": "Missing required parameters: agent_id or agent_config"
            }
            
        agent_id = params["agent_id"]
        agent_config = params["agent_config"]
        
        # Get base directories
        base_dir = Path(__file__).parent
        config_dir = base_dir / "config"
        agent_def_dir = config_dir / "agent_definitions"
        
        # Write agent definition
        agent_file = agent_def_dir / f"{agent_id}.json"
        with open(agent_file, 'w') as f:
            json.dump(agent_config, f, indent=2)
            
        # Update main config to include this agent
        config_file = config_dir / "config.json"
        if config_file.exists():
            with open(config_file, 'r') as f:
                config = json.load(f)
                
            # Add agent if not already in list
            agent_entry = {
                "id": agent_id,
                "definition_path": str(agent_file)
            }
            
            # Remove existing entry with same ID if exists
            config["agents"] = [a for a in config["agents"] if a.get("id") != agent_id]
            
            # Add new entry
            config["agents"].append(agent_entry)
            
            # Write updated config
            with open(config_file, 'w') as f:
                json.dump(config, f, indent=2)
        
        return {
            "success": True,
            "agent_id": agent_id,
            "definition_path": str(agent_file)
        }
        
    except Exception as e:
        import traceback
        return {
            "success": False,
            "error": str(e),
            "traceback": traceback.format_exc()
        }

if __name__ == "__main__":
    result = register_agent()
    print(json.dumps(result))
PYTHON;

		file_put_contents( $script_path, $script_content );
		chmod( $script_path, 0755 );
	}
	
	/**
	 * Create process_request.py script
	 */
	private function create_process_request_script() {
		$script_path = $this->scripts_dir . 'process_request.py';
		$script_content = <<<'PYTHON'
#!/usr/bin/env python3
import sys
import json
import os
from pathlib import Path

try:
    from openai import OpenAI
    import openai.agents as agents
    from openai.agents import Runner
except ImportError:
    print(json.dumps({
        "success": False,
        "error": "Required packages not installed. Run: pip install -r requirements.txt"
    }))
    sys.exit(1)

def process_request():
    """Process a user request using the OpenAI Agents SDK"""
    try:
        # Load parameters
        if len(sys.argv) <= 1 or not os.path.exists(sys.argv[1]):
            return {
                "success": False,
                "error": "No parameters file provided"
            }
            
        with open(sys.argv[1], 'r') as f:
            params = json.load(f)
            
        if not params.get("message"):
            return {
                "success": False,
                "error": "Missing required parameter: message"
            }
            
        message = params["message"]
        user_id = params.get("user_id", "anonymous")
        context = params.get("context", {})
        
        # Load SDK configuration
        base_dir = Path(__file__).parent
        config_file = base_dir / "config" / "config.json"
        
        if not config_file.exists():
            return {
                "success": False,
                "error": "SDK not initialized. Run initialize_sdk.py first"
            }
            
        with open(config_file, 'r') as f:
            config = json.load(f)
            
        # Set up OpenAI client
        client = OpenAI(api_key=config.get("api_key"))
        
        # Determine which agent(s) to use based on message content
        # This is a simplified approach - in a real implementation,
        # a more sophisticated agent selection would be performed
        
        # Load registered agents
        available_agents = []
        for agent_entry in config.get("agents", []):
            agent_path = agent_entry.get("definition_path")
            if agent_path and os.path.exists(agent_path):
                with open(agent_path, 'r') as f:
                    agent_def = json.load(f)
                    available_agents.append({
                        "id": agent_entry.get("id"),
                        "definition": agent_def
                    })
        
        if not available_agents:
            return {
                "success": False,
                "error": "No agents registered"
            }
            
        # For this example, we'll use basic intent recognition
        # to select an agent, but in practice the orchestrator
        # would handle this more intelligently
        
        # Get available tools
        tools = []
        for tool_entry in config.get("tools", []):
            tool_path = tool_entry.get("definition_path")
            if tool_path and os.path.exists(tool_path):
                with open(tool_path, 'r') as f:
                    tool_def = json.load(f)
                    tools.append(tool_def)
        
        # Create the agent runner
        # For simplicity, we're using the first agent, but in practice
        # we would select the appropriate agent based on intent
        selected_agent = available_agents[0]
        
        # Set up the agent with tool access
        agent_definition = selected_agent["definition"]
        
        # Create a runner
        runner = Runner(client=client)
        
        # Add agent
        assistant = client.beta.assistants.create(
            name=agent_definition.get("name", "Agent"),
            instructions=agent_definition.get("instructions", ""),
            model=config.get("model", "gpt-4o"),
            tools=tools
        )
        
        # Run the agent with the user's message
        thread = client.beta.threads.create()
        
        # Add message to thread
        client.beta.threads.messages.create(
            thread_id=thread.id,
            role="user",
            content=message
        )
        
        # Run the assistant
        run = client.beta.threads.runs.create(
            thread_id=thread.id,
            assistant_id=assistant.id
        )
        
        # Wait for completion
        run = client.beta.threads.runs.retrieve(
            thread_id=thread.id,
            run_id=run.id
        )
        
        # Poll until complete
        import time
        while run.status in ["queued", "in_progress"]:
            time.sleep(1)
            run = client.beta.threads.runs.retrieve(
                thread_id=thread.id,
                run_id=run.id
            )
        
        # Get messages
        messages = client.beta.threads.messages.list(
            thread_id=thread.id
        )
        
        # Extract assistant's response
        response = None
        for msg in messages.data:
            if msg.role == "assistant":
                response = msg.content[0].text.value
                break
        
        # Clean up
        client.beta.assistants.delete(assistant.id)
        
        return {
            "success": True,
            "agent_id": selected_agent["id"],
            "response": response,
            "run_id": run.id,
            "thread_id": thread.id,
            "tool_calls": [],  # Would include tool calls in real implementation
            "handoffs": []     # Would include handoffs in real implementation
        }
        
    except Exception as e:
        import traceback
        return {
            "success": False,
            "error": str(e),
            "traceback": traceback.format_exc()
        }

if __name__ == "__main__":
    result = process_request()
    print(json.dumps(result))
PYTHON;

		file_put_contents( $script_path, $script_content );
		chmod( $script_path, 0755 );
	}
	
	/**
	 * Create handoff.py script
	 */
	private function create_handoff_script() {
		$script_path = $this->scripts_dir . 'handoff.py';
		$script_content = <<<'PYTHON'
#!/usr/bin/env python3
import sys
import json
import os
from pathlib import Path
import time

try:
    from openai import OpenAI
    import openai.agents as agents
except ImportError:
    print(json.dumps({
        "success": False,
        "error": "Required packages not installed. Run: pip install -r requirements.txt"
    }))
    sys.exit(1)

def get_agent_definition(config_dir, agent_id):
    """Get agent definition from config"""
    agent_file = config_dir / "agent_definitions" / f"{agent_id}.json"
    
    if not agent_file.exists():
        return None
        
    with open(agent_file, 'r') as f:
        return json.load(f)

def get_agent_tools(config_dir, agent_id):
    """Get tools for an agent"""
    # In a real implementation, this would get the specific tools for the agent
    # For now, we'll return an empty list which means no tools
    return []

def handle_agent_handoff():
    """Handle handoff from one agent to another"""
    try:
        # Load parameters
        if len(sys.argv) <= 1 or not os.path.exists(sys.argv[1]):
            return {
                "success": False,
                "error": "No parameters file provided"
            }
            
        with open(sys.argv[1], 'r') as f:
            params = json.load(f)
            
        # Check required parameters
        required_params = ["from_agent_id", "to_agent_id", "message"]
        for param in required_params:
            if param not in params:
                return {
                    "success": False,
                    "error": f"Missing required parameter: {param}"
                }
        
        from_agent_id = params["from_agent_id"]
        to_agent_id = params["to_agent_id"]
        user_message = params["message"]
        context = params.get("context", {})
        
        # Load SDK configuration
        base_dir = Path(__file__).parent
        config_dir = base_dir / "config"
        config_file = config_dir / "config.json"
        
        if not config_file.exists():
            return {
                "success": False,
                "error": "SDK not initialized"
            }
            
        with open(config_file, 'r') as f:
            config = json.load(f)
            
        # Get the source and target agent definitions
        from_agent_def = get_agent_definition(config_dir, from_agent_id)
        to_agent_def = get_agent_definition(config_dir, to_agent_id)
        
        if not from_agent_def or not to_agent_def:
            return {
                "success": False,
                "error": f"Missing agent definition for handoff"
            }
            
        # Create handoff context
        handoff_context = {
            "from_agent": from_agent_id,
            "original_message": user_message,
            "context": context,
            "handoff_reason": "specialized_capability_needed"
        }
        
        # Process with the target agent
        client = OpenAI(api_key=config.get("api_key"))
        
        # Add the target agent with its tools
        agent_tools = get_agent_tools(config_dir, to_agent_id)
        
        assistant = client.beta.assistants.create(
            name=to_agent_def.get("name", "Agent"),
            instructions=to_agent_def.get("instructions", ""),
            model=config.get("model", "gpt-4o"),
            tools=agent_tools
        )
        
        # Create handoff message
        handoff_message = f"""
        [HANDOFF FROM {from_agent_id.upper()}]
        
        Original user request: {user_message}
        
        Handoff reason: This request requires your specialized capabilities.
        
        Context: {json.dumps(context, indent=2)}
        
        Please handle this request with your expertise.
        """
        
        # Run the agent with the handoff message
        thread = client.beta.threads.create()
        
        client.beta.threads.messages.create(
            thread_id=thread.id,
            role="user",
            content=handoff_message
        )
        
        run = client.beta.threads.runs.create(
            thread_id=thread.id,
            assistant_id=assistant.id
        )
        
        # Poll until complete
        while run.status in ["queued", "in_progress"]:
            time.sleep(1)
            run = client.beta.threads.runs.retrieve(
                thread_id=thread.id,
                run_id=run.id
            )
        
        # Get result
        messages = client.beta.threads.messages.list(
            thread_id=thread.id
        )
        
        # Extract assistant's response
        response = None
        for msg in messages.data:
            if msg.role == "assistant":
                response = msg.content[0].text.value
                break
        
        # Clean up
        client.beta.assistants.delete(assistant.id)
        
        return {
            "success": True,
            "agent_id": to_agent_id,
            "response": response,
            "run_id": run.id,
            "thread_id": thread.id,
            "handoff_context": handoff_context
        }
        
    except Exception as e:
        import traceback
        return {
            "success": False,
            "error": str(e),
            "traceback": traceback.format_exc()
        }

if __name__ == "__main__":
    result = handle_agent_handoff()
    print(json.dumps(result))
PYTHON;

		file_put_contents( $script_path, $script_content );
		chmod( $script_path, 0755 );
	}
	
	/**
	 * Create run_agent.py script
	 */
	private function create_run_agent_script() {
		$script_path = $this->scripts_dir . 'run_agent.py';
		$script_content = <<<'PYTHON'
#!/usr/bin/env python3
import sys
import json
import os
from pathlib import Path
import uuid
import time
import threading

try:
    from openai import OpenAI
    import openai.agents as agents
except ImportError:
    print(json.dumps({
        "success": False,
        "error": "Required packages not installed. Run: pip install -r requirements.txt"
    }))
    sys.exit(1)

# Global storage for running tasks
# In a production environment, this would be stored in a database
RUNNING_TASKS = {}

def get_agent_definition(config_dir, agent_id):
    """Get agent definition from config"""
    agent_file = config_dir / "agent_definitions" / f"{agent_id}.json"
    
    if not agent_file.exists():
        return None
        
    with open(agent_file, 'r') as f:
        return json.load(f)

def run_task_in_background(task_id, agent_id, task_description, parameters, config):
    """Run a task in the background"""
    try:
        # Update task status
        RUNNING_TASKS[task_id]["status"] = "running"
        RUNNING_TASKS[task_id]["started_at"] = time.time()
        
        # Set up OpenAI client
        client = OpenAI(api_key=config.get("api_key"))
        
        # Get agent definition
        base_dir = Path(__file__).parent
        config_dir = base_dir / "config"
        agent_def = get_agent_definition(config_dir, agent_id)
        
        if not agent_def:
            RUNNING_TASKS[task_id]["status"] = "failed"
            RUNNING_TASKS[task_id]["error"] = f"Agent definition not found for {agent_id}"
            return
        
        # Create assistant for this task
        assistant = client.beta.assistants.create(
            name=agent_def.get("name", "Agent"),
            instructions=agent_def.get("instructions", ""),
            model=config.get("model", "gpt-4o"),
            tools=[]  # No tools for now
        )
        
        # Create thread and add task description
        thread = client.beta.threads.create()
        
        # Construct message with task details
        task_message = f"""
        Task: {task_description}
        
        Parameters: {json.dumps(parameters, indent=2)}
        
        Please complete this task step by step.
        """
        
        client.beta.threads.messages.create(
            thread_id=thread.id,
            role="user",
            content=task_message
        )
        
        # Run the assistant
        run = client.beta.threads.runs.create(
            thread_id=thread.id,
            assistant_id=assistant.id
        )
        
        # Store run info
        RUNNING_TASKS[task_id]["run_id"] = run.id
        RUNNING_TASKS[task_id]["thread_id"] = thread.id
        RUNNING_TASKS[task_id]["assistant_id"] = assistant.id
        
        # Poll until complete
        while run.status in ["queued", "in_progress"]:
            time.sleep(2)
            run = client.beta.threads.runs.retrieve(
                thread_id=thread.id,
                run_id=run.id
            )
            
            # Update progress (50% complete when halfway through estimated time)
            elapsed = time.time() - RUNNING_TASKS[task_id]["started_at"]
            estimated_total = 120  # Just a guess for now
            progress = min(90, int(elapsed / estimated_total * 100))
            RUNNING_TASKS[task_id]["progress"] = progress
        
        # Get result
        messages = client.beta.threads.messages.list(
            thread_id=thread.id
        )
        
        # Extract assistant's response
        response = None
        for msg in messages.data:
            if msg.role == "assistant":
                response = msg.content[0].text.value
                break
        
        # Update task status
        if run.status == "completed":
            RUNNING_TASKS[task_id]["status"] = "completed"
            RUNNING_TASKS[task_id]["result"] = response
            RUNNING_TASKS[task_id]["progress"] = 100
        else:
            RUNNING_TASKS[task_id]["status"] = "failed"
            RUNNING_TASKS[task_id]["error"] = f"Run finished with status {run.status}"
            
        RUNNING_TASKS[task_id]["completed_at"] = time.time()
        
        # Clean up (in a real implementation, we might keep these for a while)
        try:
            client.beta.assistants.delete(assistant.id)
        except:
            pass
            
    except Exception as e:
        import traceback
        RUNNING_TASKS[task_id]["status"] = "failed"
        RUNNING_TASKS[task_id]["error"] = str(e)
        RUNNING_TASKS[task_id]["traceback"] = traceback.format_exc()
        RUNNING_TASKS[task_id]["completed_at"] = time.time()

def start_task(agent_id, task_description, parameters, user_id, config):
    """Start a new task"""
    task_id = str(uuid.uuid4())
    
    # Create task record
    RUNNING_TASKS[task_id] = {
        "task_id": task_id,
        "agent_id": agent_id,
        "description": task_description,
        "parameters": parameters,
        "user_id": user_id,
        "status": "pending",
        "progress": 0,
        "created_at": time.time(),
        "started_at": None,
        "completed_at": None,
        "result": None,
        "error": None,
    }
    
    # Start background thread
    thread = threading.Thread(
        target=run_task_in_background,
        args=(task_id, agent_id, task_description, parameters, config)
    )
    thread.daemon = True
    thread.start()
    
    return task_id

def get_task_status(task_id):
    """Get status of a task"""
    if task_id not in RUNNING_TASKS:
        return {
            "success": False,
            "error": f"Task {task_id} not found"
        }
        
    task_info = RUNNING_TASKS[task_id].copy()
    
    # Format timestamps
    for key in ["created_at", "started_at", "completed_at"]:
        if task_info.get(key):
            task_info[key] = time.strftime("%Y-%m-%d %H:%M:%S", time.localtime(task_info[key]))
    
    return {
        "success": True,
        "task": task_info
    }

def run_agent():
    """Handle running agent operations"""
    try:
        # Load parameters
        if len(sys.argv) <= 1 or not os.path.exists(sys.argv[1]):
            return {
                "success": False,
                "error": "No parameters file provided"
            }
            
        with open(sys.argv[1], 'r') as f:
            params = json.load(f)
            
        action = params.get("action", "")
        
        if action not in ["start", "status"]:
            return {
                "success": False,
                "error": f"Invalid action: {action}"
            }
            
        # Load SDK configuration
        base_dir = Path(__file__).parent
        config_file = base_dir / "config" / "config.json"
        
        if not config_file.exists():
            return {
                "success": False,
                "error": "SDK not initialized"
            }
            
        with open(config_file, 'r') as f:
            config = json.load(f)
        
        # Handle different actions
        if action == "start":
            # Required parameters for starting a task
            required_params = ["agent_id", "task_description"]
            for param in required_params:
                if param not in params:
                    return {
                        "success": False,
                        "error": f"Missing required parameter: {param}"
                    }
                    
            agent_id = params["agent_id"]
            task_description = params["task_description"]
            parameters = params.get("parameters", {})
            user_id = params.get("user_id", 0)
            
            task_id = start_task(agent_id, task_description, parameters, user_id, config)
            
            return {
                "success": True,
                "task_id": task_id,
                "status": "pending"
            }
            
        elif action == "status":
            # Required parameters for checking status
            if "task_id" not in params:
                return {
                    "success": False,
                    "error": "Missing required parameter: task_id"
                }
                
            task_id = params["task_id"]
            return get_task_status(task_id)
        
    except Exception as e:
        import traceback
        return {
            "success": False,
            "error": str(e),
            "traceback": traceback.format_exc()
        }

if __name__ == "__main__":
    result = run_agent()
    print(json.dumps(result))
PYTHON;

		file_put_contents( $script_path, $script_content );
		chmod( $script_path, 0755 );
	}
}