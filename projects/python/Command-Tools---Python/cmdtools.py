import subprocess

def soutstr(cmd):
	""" Executes a shell command and returns the stdout as a string. """
	sout = subprocess.Popen(cmd, shell=True, stdout=subprocess.PIPE)
	sout = sout.communicate()[0]
	sout = sout.strip() # Strip any whitespace out.
	sout = sout.decode() # Decode from "byte" to "str" type.
	return sout

if __name__ == "__main__":
	print("You ran this module directly (and did not 'import' it).")
	input("\nPress any key to continue...")

