<?php
/**
 * Base Tool Class
 *
 * @package WP_Natural_Language_Commands
 */

namespace WPNaturalLanguageCommands\Tools;

use WPNaturalLanguageCommands\Includes\ToolRegistry;

if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Abstract base class for all tools.
 *
 * This class defines the interface that all tools must implement.
 * It provides common functionality and ensures consistent behavior
 * across all tool implementations.
 */
abstract class BaseTool {

    /**
     * Tool name.
     *
     * @var string
     */
    protected $name;

    /**
     * Tool description.
     *
     * @var string
     */
    protected $description;

    /**
     * Constructor.
     */
    public function __construct() {
        $this->register();
    }

    /**
     * Register the tool with the tool registry.
     */
    public function register() {
        // Get the tool registry instance
        $registry = ToolRegistry::get_instance();
        
        // Register this tool
        $registry->register_tool( $this );
    }

    /**
     * Get the tool name.
     *
     * @return string The tool name.
     */
    public function get_name() {
        return $this->name;
    }

    /**
     * Get the tool description.
     *
     * @return string The tool description.
     */
    public function get_description() {
        return $this->description;
    }

    /**
     * Get the tool parameters for OpenAI function calling.
     *
     * @return array The tool parameters.
     */
    abstract public function get_parameters();

    /**
     * Execute the tool with the given parameters.
     *
     * @param array $params The parameters to use when executing the tool.
     * @return array|\WP_Error The result of executing the tool.
     */
    abstract public function execute( $params );

    /**
     * Validate the parameters before executing the tool.
     *
     * @param array $params The parameters to validate.
     * @return bool|\WP_Error True if valid, \WP_Error otherwise.
     */
    protected function validate_parameters( $params ) {
        $required_params = array_filter( $this->get_parameters(), function( $param ) {
            return isset( $param['required'] ) && $param['required'] === true;
        } );

        foreach ( $required_params as $name => $param ) {
            if ( ! isset( $params[ $name ] ) || empty( $params[ $name ] ) ) {
                return new \WP_Error(
                    'missing_required_parameter',
                    sprintf( 'Missing required parameter: %s', $name )
                );
            }
        }

        return true;
    }

    /**
     * Apply default values to parameters.
     *
     * @param array $params The parameters passed to the tool at execution time.
     * @return array The parameters with defaults applied.
     */
    protected function apply_parameter_defaults( $params ) {
        $parameters = $this->get_parameters();
        
        foreach ( $parameters as $name => $param ) {
            $param_not_given = ! isset( $params[ $name ] ) || $params[ $name ] === null;
            if ( $param_not_given && isset( $param['default'] ) ) {
                $params[ $name ] = $param['default'];
            }
        }
        
        return $params;
    }
    
    /**
     * Get a human-readable summary of the tool execution result.
     * 
     * This method can be overridden by child classes to provide custom summaries.
     * By default, it tries to extract a message from the result or returns a generic message.
     *
     * @param array|\WP_Error $result The result of executing the tool.
     * @param array $params The parameters used when executing the tool.
     * @return string A human-readable summary of the result.
     */
    public function get_result_summary( $result, $params ) {
        if ( is_wp_error( $result ) ) {
            return $result->get_error_message();
        }
        
        if ( isset( $result['message'] ) ) {
            return $result['message'];
        }
        
        // Default generic summary
        return sprintf( 'Executed %s successfully.', $this->get_name() );
    }
}
